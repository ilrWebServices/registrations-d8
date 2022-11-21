<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager;

/**
 * Class OrderCreateSubscriber.
 *
 * @package Drupal\ilr_registrations\EventSubscriber
 */
class OrderCreateSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The ILR Outreach discount manager.
   *
   * @var \Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager
   */
  protected $discountManager;

  /**
   * Constructs a new OrderCreateSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\ilr_outreach_discount_api\IlrOutreachDiscountManager $discount_manager
   *   The ILR Outreach discount manager.
   */
  public function __construct(RequestStack $request_stack, IlrOutreachDiscountManager $discount_manager) {
    $this->requestStack = $request_stack;
    $this->discountManager = $discount_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    $events = [
      OrderEvents::ORDER_CREATE => 'onOrderCreate',
    ];
    return $events;
  }

  /**
   * React to a commerce order when created.
   */
  public function onOrderCreate(OrderEvent $event) {
    // Add any stored discount or UTM codes in the persistent visitor parameters
    // cookie in the order data. The persistent_visitor_parameters module is
    // installed on the www.ilr.cornell.edu site. The cookie is configured for
    // the .ilr.cornell.edu domain, so it will also be available here. See
    // SerializedOrderManager::getObjectForOrder().
    $pvp_stored_variables = $this->requestStack->getCurrentRequest()->cookies->get('pvp_stored_variables');
    $discount_codes = [];
    $utm_codes = [];
    $order = $event->getOrder();

    if ($pvp_stored_variables) {
      $pvp_variables = unserialize($pvp_stored_variables);

      if (is_array($pvp_variables)) {
        foreach ($pvp_variables as $key => $val) {
          $val = preg_replace("/[^a-z0-9'\/]/i", '', $val);

          if (strpos($key, 'utm_') === 0) {
            $utm_codes[$key] = $val;
          }
          elseif ($key === 'ilr_discount_code') {
            $discount_codes[] = $val;
          }
        }
      }
    }

    if ($utm_codes) {
      $order->setData('utm_codes', $utm_codes);
    }

    // Look for any discount codes in an env var.
    if ($env_disount_codes = getenv('ILR_DISCOUNT_CODES')) {
      $discount_codes = array_merge($discount_codes, explode(';', $env_disount_codes));
    }

    // As of late 2022, ilr_outreach_discounts can only be added to
    // `registration` orders.
    if ($order->bundle() === 'registration' && $discount_codes && $discount = $this->discountManager->getEligibleDiscount($discount_codes[0])) {
      // Note that getEligibleDiscount() is called without a $class_sf_id.
      // This could allow two types of discount codes being added to the
      // order: 1) Universal discounts, possibly with exceptions, or 2)
      // Non-universal discounts with inclusions, even if one of those
      // inclusions is not for the class being added as this order/cart is
      // created. IlrOutreachDiscountOrderProcessor::process() should then
      // properly apply the discounts even if we've added one here that
      // shouldn't really apply.
      $ilr_outreach_discounts = [$discount->code => $discount];
      $order->setData('ilr_outreach_discounts', $ilr_outreach_discounts);
    }
  }

}
