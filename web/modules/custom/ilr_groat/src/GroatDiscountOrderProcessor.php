<?php

namespace Drupal\ilr_groat;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Adjustment;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
* Provides an order processor that modifies groat single ticket prices according to class year.
*/
class GroatDiscountOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

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
        // $participant_name = $participant->field_address->first()->getGivenName() . ' ' . $participant->field_address->first()->getFamilyName();

        /** @var \Drupal\commerce_price\Price $full_price */
        $full_price = $product_variation->getPrice();
        $adjusted_price = new Price($full_price->getNumber(), $full_price->getCurrencyCode());

        if ($years_graduated < 3) {
          if (!$product_variation->field_new_grad_price->isEmpty()) {
            $adjusted_price = $product_variation->field_new_grad_price->first()->toPrice();
          }
        }
        elseif ($years_graduated < 11) {
          if (!$product_variation->field_recent_grad_price->isEmpty()) {
            $adjusted_price = $product_variation->field_recent_grad_price->first()->toPrice();
          }
        }

        if (!$adjusted_price->equals($full_price)) {
          $discount_price = $adjusted_price->subtract($full_price);
          $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

          $adjustments[] = new Adjustment([
            'type' => 'alumni_discount',
            'label' => $this->t('Price adjustment for @year graduate to @adjusted_price', [
              '@year' => $class_year,
              '@adjusted_price' => $currency_formatter->format($adjusted_price->getNumber(), $adjusted_price->getCurrencyCode()),
            ]),
            'amount' => $discount_price,
            'source_id' => $participant->id(),
          ]);
        }
      }

      $order_item->setAdjustments($adjustments);
      $order_item->save();
    }
  }

}
