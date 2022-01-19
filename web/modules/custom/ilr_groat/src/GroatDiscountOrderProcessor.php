<?php

namespace Drupal\ilr_groat;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Adjustment;

/**
* Provides an order processor that modifies groat single ticket prices according to class year.
*/
class GroatDiscountOrderProcessor implements OrderProcessorInterface {

  /**
  * {@inheritdoc}
  */
  public function process(OrderInterface $order)  {
    if ($order->bundle() !== 'groat_alpern_awards') {
      return;
    }

    foreach ($order->getItems() as $order_item) {
      $product_variation = $order_item->getPurchasedEntity();

      if ($product_variation->getSku() !== 'groat-single-full') {
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
        $class_year = $participant->field_class_year->value ?? 0;
        $years_graduated = date('Y') - $class_year;
        $participant_name = $participant->field_address->first()->getGivenName() . ' ' . $participant->field_address->first()->getFamilyName();

        if ($years_graduated < 3) {
          $adjustments[] = new Adjustment([
            'type' => 'alumni_discount',
            'label' => 'Price adjustment for ' . $participant_name,
            'amount' => new Price('-240', 'USD'),
            'source_id' => $participant->id(),
          ]);
        }
        elseif ($years_graduated < 11) {
          $adjustments[] = new Adjustment([
            'type' => 'alumni_discount',
            'label' => 'Price adjustment for ' . $participant_name,
            'amount' => new Price('-190', 'USD'),
            'source_id' => $participant->id(),
          ]);
        }
      }

      $order_item->setAdjustments($adjustments);
      $order_item->save();
    }
  }

}
