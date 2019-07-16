<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;

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
   * Constructs a new SerializedOrderService object.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectForOrder(OrderInterface $order) {
    $promotion_storage = $this->entityManager->getStorage('commerce_promotion');
    $sf_mapping_storage = $this->entityManager->getStorage('salesforce_mapped_object');

    $items = $order->getItems();
    $customer = $order->getCustomer();
    $billing_profile = $order->getBillingProfile();
    $payments = $this->entityManager->getStorage('commerce_payment')->loadByProperties([
      'order_id' => $order->id(),
    ]);
    $payment = reset($payments);
    $payment_gateway = $payment->getPaymentGateway();

    // Note that this function makes a remote call to the FreedomPay API.
    // @todo Deal with possible remote transaction retrieval failure.
    $transaction = $payment_gateway->getPlugin()->getTransaction($payment->getRemoteId());


    $response = [
      "point_of_sale" => $payment_gateway->id(),
      "order_id" => $order->id(),
      "payments" => [
        [
          "payment_type" => $payment_gateway->getPluginId(), //"freedompay_cc",
          "payment_id" => $payment->id(),
          "amount" => (float) $payment->getAmount()->getNumber(),
          "transaction_id" => $payment->getRemoteId(),
          "transaction_data" => $transaction,
        ]
      ],
      "customer" => [
        "contact_sfid" => null,
        "email" => $billing_profile->uid->entity->mail->value,
        "billing_email" => $billing_profile->field_email->value,
        "first_name" => $billing_profile->field_first_name->value,
        "last_name" => $billing_profile->field_last_name->value,
        "company" => $billing_profile->field_organization->value,
        "job_title" => $billing_profile->field_job_title->value,
        "phone" => $billing_profile->field_phone->value,
        "additional_fields" => [], // @todo
      ],
      "order_total" => (float) $order->getTotalPaid()->getNumber(),
      "items" => [],
      "discounts" => [], // @todo These might be full order adjustments.
    ];

    foreach ($items as $item) {
      $discounts = [];
      $item_adjustments = $item->getAdjustments();

      foreach ($item_adjustments as $item_adjustment) {
        $promotions = $promotion_storage->loadByProperties([
          'promotion_id' => $item_adjustment->getSourceId(),
        ]);

        if (count($promotions)) {
          foreach ($promotions as $promotion) {
            $sf_promo_mapped_objects = $sf_mapping_storage->loadByEntity($promotion);
            $sf_promo_mapped_object = reset($sf_promo_mapped_objects);

            $discount = [
              "sfid" => $sf_promo_mapped_object->sfid(),
              "code" => $promotion->label(),
              "type" => $item_adjustment->getPercentage() ? 'percentage' : 'amount',
              "amount" => (float) $item_adjustment->getAmount()->getNumber(),
              "percentage" => (float) $item_adjustment->getPercentage(),
            ];

            $discounts[] = $discount;
          }
        }
      }

      $participants = [];
      $registration = $this->entityManager->getStorage('registration')->loadByProperties([
        'commerce_order_item_id' => $item->id(),
      ]);

      if ($registration) {
        $registration = reset($registration);
        foreach ($registration->participants as $participant) {
          $participants[] = [
            "contact_sfid" => null,
            'email' => $participant->entity->mail->value,
            "first_name" => $participant->entity->field_first_name->value,
            "last_name" => $participant->entity->field_last_name->value,
            'additional_fields' => [], // @todo
          ];
        }
      }

      $response['items'][] = [
        'name' => $item->getTitle(),
        "discounts" => $discounts,
        "price" => (float) $item->getUnitPrice()->getNumber(),
        "discounted_price" => (float) $item->getAdjustedUnitPrice()->getNumber(),
        "quantity" => (int) $item->getQuantity(),
        "total" => (float) $item->getTotalPrice()->getNumber(),
        "discounted_total" => (float) $item->getAdjustedTotalPrice()->getNumber(),
        "registration" => [
          "description" => "For example, a single course with 1+ participants.",
          "product_type" => "Open Enrollment",
          "additional_fields" => [],
          "course_id" => $item->getData('sf_course_id'),
          "class_id" => $item->getData('sf_class_id'),
          "participants" => $participants,
        ]
      ];
    }

    return $response;
  }

}
