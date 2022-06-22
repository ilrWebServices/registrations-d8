<?php

namespace Drupal\ilr_outreach_discounts;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
* Provides an order processor that calculates ILR Outreach discounts.
*/
class IlrOutreachDiscountOrderProcessor implements OrderProcessorInterface {

  use StringTranslationTrait;

  /**
  * {@inheritdoc}
  */
  public function process(OrderInterface $order) {
    /** @var \Drupal\ilr_outreach_discounts\IlrOutreachDiscount $discount */
    foreach ($order->getData('ilr_outreach_discounts', []) as $discount) {
      foreach ($order->getItems() as $order_item) {
        // If this is not a universal discount, only apply it to this item if
        // the discount applies to this class.
        if (!$discount->universal && !in_array($order_item->getData('sf_class_id'), $discount->appliesTo)) {
          continue;
        }

        if ($discount->type === 'percentage') {
          $adjustment_amount = $order_item->getUnitPrice()->multiply($discount->value)->multiply($order_item->getQuantity());
        }
        else {
          $adjustment_amount = $order_item->getUnitPrice()->add(new Price($discount->value, 'USD'))->multiply($order_item->getQuantity());
        }

        // Universal discounts apply to every item in the order, so they can
        // have a simple label and a source_id that groups them together.
        if ($discount->universal) {
          $label = $this->t('@discount_code discount', [
            '@discount_code' => $discount->code,
          ]);
          $source_id = $discount->code;
        }
        else {
          $label = $this->t('@discount_code discount for @class', [
            '@discount_code' => $discount->code,
            '@class' => $order_item->label(),
          ]);
          $source_id = $discount->code . '_' . $order_item->id();
        }

        $order_item->addAdjustment(new Adjustment([
          'type' => 'ilr_outreach_discount',
          'label' => $label,
          'source_id' => $source_id,
          'amount' => $adjustment_amount,
          'percentage' => ($discount->type === 'percentage') ? (string) $discount->value : NULL,
        ]));

        $order_item->save();
      }
    }
  }

}
