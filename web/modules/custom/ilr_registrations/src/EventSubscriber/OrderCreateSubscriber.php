<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\ilr_analytics_session\IlrAnalyticsSessionManager;

/**
 * Event subscriber for order creation.
 *
 * @package Drupal\ilr_registrations\EventSubscriber
 */
class OrderCreateSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new OrderCreateSubscriber object.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected IlrAnalyticsSessionManager $anlyticsSessionManager
  ) {}

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents(): array {
    $events = [
      OrderEvents::ORDER_CREATE => 'onOrderCreate',
    ];
    return $events;
  }

  /**
   * React to a commerce order when created.
   */
  public function onOrderCreate(OrderEvent $event) {
    // Add any stored UTM codes in the persistent visitor parameters cookie in
    // the order data. The persistent_visitor_parameters module is installed on
    // the www.ilr.cornell.edu site. The cookie is configured for the
    // .ilr.cornell.edu domain, so it will also be available here. See
    // SerializedOrderManager::getObjectForOrder().
    $pvp_stored_variables = $this->requestStack->getCurrentRequest()->cookies->get('pvp_stored_variables');
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
        }
      }
    }

    if ($utm_codes) {
      $order->setData('utm_codes', $utm_codes);
    }

    // Add the analytics id (usually from _ga cookie value) to the order for
    // later use.
    $order->setData('analytics_session_id', $this->anlyticsSessionManager->getClientId());
  }

}
