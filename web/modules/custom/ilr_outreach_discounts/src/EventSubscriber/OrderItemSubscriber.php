<?php

namespace Drupal\ilr_outreach_discounts\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\commerce_order\Event\OrderItemEvent;

/**
 * Class OrderItemSubscriber.
 */
class OrderItemSubscriber implements EventSubscriberInterface {

  /**
   * The ILR Outreach discount manager.
   *
   * @var \Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager
   */
  protected $discountManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new OrderItemSubscriber object.
   *
   * @param \Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager $discount_manager
   *   The ILR Outreach discount manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(IlrOutreachDiscountManager $discount_manager, RequestStack $request_stack) {
    $this->discountManager = $discount_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_ITEM_INSERT => 'onOrderItemInsert'
    ];
  }

  /**
   *
   */
  public function onOrderItemInsert(OrderItemEvent $event) {
    $item = $event->getOrderItem();

    // $order->bundle() === 'registration'
    if ($item->bundle() !== 'class' || !$item->getData('sf_class_id')) {
      return;
    }

    $pvp_stored_variables = $this->requestStack->getCurrentRequest()->cookies->get('pvp_stored_variables');
    $discount_codes = [];

    if ($pvp_stored_variables) {
      $pvp_variables = unserialize($pvp_stored_variables);

      if (is_array($pvp_variables)) {
        foreach ($pvp_variables as $key => $val) {
          $val = preg_replace("/[^a-z0-9'\/]/i", '', $val);

          if ($key === 'ilr_discount_code') {
            $discount_codes[] = $val;
          }
        }
      }
    }

    // Look for any discount codes in an env var.
    if ($env_disount_codes = getenv('ILR_DISCOUNT_CODES')) {
      $discount_codes = array_merge($discount_codes, explode(';', $env_disount_codes));
    }

    if ($discount_codes && $discount = $this->discountManager->getEligibleDiscount($discount_codes[0], $item->getData('sf_class_id'))) {
      $_SESSION['ilr_discount_codes'] = [$discount->code => $discount];
    }
    else {
      unset($_SESSION['ilr_discount_codes']);
    }
  }

}
