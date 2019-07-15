<?php

namespace Drupal\ilr_registrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SerializedOrderController.
 */
class SerializedOrderController extends ControllerBase {

  const POS_ID = 'register.ilr.cornell.edu';

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new SerializedOrderController object.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Hello.
   *
   * @todo Refactor payment objects to allow for payment types other than credit
   * card.
   * @todo Verify hardcoded fields on the billing profile (e.g.
   * field_organization and the name fields).
   *
   * @return string Return Hello string.
   */
  public function load(OrderInterface $commerce_order) {
    $order = $commerce_order;
    $items = $order->getItems();
    $customer = $order->getCustomer();
    $billing_profile = $order->getBillingProfile();
    $payments = $this->entityManager->getStorage('commerce_payment')->loadByProperties([
      'order_id' => $order->id(),
    ]);
    $payment = reset($payments);
    $payment_gateway = $payment->getPaymentGateway();

    // @todo Deal with remote transaction retrieval failure.
    $transaction = $payment_gateway->getPlugin()->getTransaction($payment->getRemoteId());

    $promotion_storage = $this->entityManager->getStorage('commerce_promotion');
    $sf_mapping_storage = $this->entityManager->getStorage('salesforce_mapped_object');

    $response = [
      "point_of_sale" => $this::POS_ID,
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
        "user_email" => $billing_profile->uid->entity->mail->value,
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

        $class_product_variation = $item->getPurchasedEntity();
        $course_product = $class_product_variation->getProduct();

        $sf_variation_mapped_objects = $sf_mapping_storage->loadByEntity($class_product_variation);
        $sf_variation_mapped_object = reset($sf_variation_mapped_objects);

        $sf_product_mapped_objects = $sf_mapping_storage->loadByEntity($course_product);
        $sf_product_mapped_object = reset($sf_product_mapped_objects);
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
          "course_id" => $sf_product_mapped_object->sfid(),
          "class_id" => $sf_variation_mapped_object->sfid(),
          "additional_fields" => [],
          "participants" => $participants,
        ]
      ];
    }

    return new JsonResponse($response);
  }

}
