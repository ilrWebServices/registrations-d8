<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SerializedOrderService.
 */
class SerializedOrderManager implements SerializedOrderManagerInterface {

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Symfony\Component\HttpFoundation\Request definition.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new SerializedOrderService object.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectForOrder(OrderInterface $order) {
    $payment_storage = $this->entityManager->getStorage('commerce_payment');
    $promotion_storage = $this->entityManager->getStorage('commerce_promotion');
    $sf_mapping_storage = $this->entityManager->getStorage('salesforce_mapped_object');
    $registration_storage = $this->entityManager->getStorage('registration');

    $items = $order->getItems();
    $customer = $order->getCustomer();
    $billing_profile = $order->getBillingProfile();
    $billing_address = $billing_profile->address->first()->getValue();

    $response = [
      "point_of_sale" => $this->configFactory->get('system.site')->get('name') . ' : ' . $this->request->getHost(),
      "order_id" => $order->id(),
      "payments" => [],
      "customer" => [
        "contact_sfid" => null, // @todo Lookup a mapped value.
        "email" => $billing_profile->uid->entity->mail->value,
        "billing_email" => $billing_profile->field_email->value,
        "first_name" => $billing_address['given_name'],
        "last_name" => $billing_address['family_name'],
        "company" => $billing_address['organization'],
        "address_line1" => $billing_address['address_line1'],
        "address_line2" => $billing_address['address_line2'],
        "city" => $billing_address['locality'],
        "state" => $billing_address['administrative_area'],
        "zip" => $billing_address['postal_code'],
        "country_code" => $billing_address['country_code'],
        "job_title" => $billing_profile->field_job_title->value,
        "phone" => $billing_profile->field_phone->value,
        "additional_fields" => [], // @todo
      ],
      "order_total" => (float) $order->getTotalPaid()->getNumber(),
      "order_items" => [], // Set below.
    ];

    // Process payments.
    $commerce_payments = $payment_storage->loadByProperties([
      'order_id' => $order->id(),
      'state' => 'completed',
    ]);

    if (!empty($commerce_payments)) {
      $payments = [];

      foreach ($commerce_payments as $commerce_payment) {
        $payment_gateway = $commerce_payment->getPaymentGateway();

        // Note that this function makes a remote call to the FreedomPay API.
        // @todo Deal with possible remote transaction retrieval failure.
        $transaction = $payment_gateway->getPlugin()->getTransaction($commerce_payment->getRemoteId());

        $payments[] = [
          "payment_type" => $payment_gateway->getPluginId(), //"freedompay_cc",
          "payment_id" => $commerce_payment->id(),
          "amount" => (float) $commerce_payment->getAmount()->getNumber(),
          "transaction_id" => $commerce_payment->getRemoteId(),
          "transaction_data" => $transaction,
        ];
      }

      $response['payments'] = $payments;
    }

    // Process order items.
    foreach ($items as $item) {
      $discounts = [];
      $item_adjustments = $item->getAdjustments();

      // Process discounts for this item.
      foreach ($item_adjustments as $item_adjustment) {
        $promotions = $promotion_storage->loadByProperties([
          'promotion_id' => $item_adjustment->getSourceId(),
        ]);

        if (!empty($promotions)) {
          foreach ($promotions as $promotion) {
            $sf_promo_mapped_objects = $sf_mapping_storage->loadByEntity($promotion);
            $sf_promo_mapped_object = reset($sf_promo_mapped_objects);

            $discount = [
              "sfid" => $sf_promo_mapped_object->sfid(),
              "code" => $promotion->label(),
              "type" => $item_adjustment->getPercentage() ? 'percentage' : 'fixed_amount',
              "amount" => (float) $item_adjustment->getAmount()->getNumber(),
              "percentage" => (float) $item_adjustment->getPercentage(),
            ];

            $discounts[] = $discount;
          }
        }
      }

      // Process registrations and participants for this item.
      $participants = [];
      $registrations = $registration_storage->loadByProperties([
        'commerce_order_item_id' => $item->id(),
      ]);

      if (!empty($registrations)) {
        $registration = reset($registrations);

        foreach ($registration->participants->referencedEntities() as $participant) {
          // @todo - handle participants even if there is not an address field
          $address_value = $participant->field_address->getValue();
          $address = reset($address_value);
          $participants[] = [
            "contact_sfid" => null,
            'email' => $participant->mail->value,
            "first_name" => $address['given_name'],
            "last_name" => $address['family_name'],
            "company" => $address['organization'],
            "address_line1" => $address['address_line1'],
            "address_line2" => $address['address_line2'],
            "city" => $address['locality'],
            "state" => $address['administrative_area'],
            "zip" => $address['postal_code'],
            "country_code" => $address['country_code'],
            "job_title" => $participant->hasField('field_job_title') ? $participant->field_job_title->value : NULL,
            "phone" => $participant->hasField('field_phone') ? $participant->field_phone->value : NULL,
            "dietary_restrictions" => $participant->hasField('field_dietary_restrictions') ? $participant->field_dietary_restrictions->value : NULL,
            "accessible_accommodation" => $participant->hasField('field_accessible_accommodation') ? $participant->field_accessible_accommodation->value : NULL,
            "is_cornell_employee" => $participant->hasField('field_is_cornell_employee') ? ($participant->field_is_cornell_employee->value ? 'true' : 'false') : NULL,
            "apply_to_certificate" => "",
            'additional_fields' => [], // @todo
          ];
        }
      }

      $response['order_items'][] = [
        'name' => $item->getTitle(),
        "discounts" => $discounts,
        "price" => (float) $item->getUnitPrice()->getNumber(),
        "discounted_price" => (float) $item->getAdjustedUnitPrice()->getNumber(),
        "quantity" => (int) $item->getQuantity(),
        "total" => (float) $item->getTotalPrice()->getNumber(),
        "discounted_total" => (float) $item->getAdjustedTotalPrice()->getNumber(),
        "product" => [
          "product_type" => "registration",
          "x_product_type_subtype" => $item->bundle(), // Not in the spec, but maybe still useful.
          "additional_fields" => [], // @todo
          "description" => "",
          "course_id" => $item->getData('sf_course_id'),
          "class_id" => $item->getData('sf_class_id'),
          "participants" => $participants,
        ]
      ];
    }

    return $response;
  }

}
