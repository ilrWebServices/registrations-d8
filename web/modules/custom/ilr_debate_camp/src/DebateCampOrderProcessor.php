<?php

namespace Drupal\ilr_debate_camp;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * An order processor that modifies summer debate camp prices by location.
 */
class DebateCampOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if ($order->bundle() !== 'summer_debate_camp') {
      return;
    }

    foreach ($order->getItems() as $order_item) {
      $product_variation = $order_item->getPurchasedEntity();

      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
      if ($product_variation->getSku() !== 'summer-debate-full') {
        continue;
      }

      $adjustments = $order_item->getAdjustments();
      $registration_storage = \Drupal::service('entity_type.manager')->getStorage('registration');

      // Get the registration for this order item id.
      $registrations = $registration_storage->loadByProperties([
        'commerce_order_item_id' => $order_item->id(),
      ]);

      if (empty($registrations)) {
        continue;
      }

      $registration = reset($registrations);

      foreach ($registration->participants->referencedEntities() as $participant) {
        // Add the housing fee if this participant is residential. The housing
        // price is stored on a custom field on the variation.
        if ($participant->field_housing_type->value === 'residential' && !$product_variation->field_housing_price->isEmpty()) {
          $adjustments[] = new Adjustment([
            'type' => 'housing_fee',
            'label' => $this->t('Residential housing fee for @fname @lname', [
              '@fname' => $participant->field_student_first_name->value ?? 'Participant',
              '@lname' => $participant->field_student_last_name->value ?? $participant->id(),
            ]),
            'amount' => $product_variation->field_housing_price->first()->toPrice(),
            'source_id' => $participant->id(),
          ]);
        }
      }

      if (empty($adjustments)) {
        return;
      }

      $order_item->setAdjustments($adjustments);
      $order_item->save();
    }
  }

}
