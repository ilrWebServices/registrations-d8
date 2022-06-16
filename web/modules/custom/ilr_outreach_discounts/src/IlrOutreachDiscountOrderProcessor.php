<?php

namespace Drupal\ilr_outreach_discounts;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\commerce_order\Adjustment;
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
    foreach ($order->getData('ilr_outreach_discounts', []) as $discount_code => $discount) {
      foreach ($order->getItems() as $order_item) {
        // Remove existing ilr_outreach_discount adjustments.
        // foreach ($order_item->getAdjustments(['ilr_outreach_discounts']) as $adjustment) {
        //   $order_item->removeAdjustment($adjustment);
        // }

        if ($discount['type'] === 'percentage') {
          $adjustment_amount = $order_item->getUnitPrice()->multiply($discount['value'])->multiply($order_item->getQuantity());
        }
        else {
          $adjustment_amount = $order_item->getUnitPrice()->add($discount['value'])->multiply($order_item->getQuantity());
        }

        $order_item->addAdjustment(new Adjustment([
          'type' => 'ilr_outreach_discount',
          'label' => $this->t('@discount_code discount', [
            '@discount_code' => $discount_code,
          ]),
          // This will group this discount code in the order summary. Set
          // 'source_id' => $discount_code . '_' . $order_item->id() for
          // separate adjustment lines per order item.
          'source_id' => $discount_code,
          'amount' => $adjustment_amount,
          'percentage' => ($discount['type'] === 'percentage') ? (string) $discount['value'] : NULL,
        ]));

        $order_item->save();
      }
    }
  }

}
