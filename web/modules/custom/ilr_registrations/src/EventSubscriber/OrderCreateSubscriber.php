<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * Constructs a new OrderCreateSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
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
    // Add any stored UTM codes in the persistent visitor parameters cookie in
    // the order data. The persistent_visitor_parameters module is installed on
    // the www.ilr.cornell.edu site. The cookie is configured for the
    // .ilr.cornell.edu domain, so it will also be available here. See
    // SerializedOrderManager::getObjectForOrder().
    $pvp_stored_variables = $this->requestStack->getCurrentRequest()->cookies->get('pvp_stored_variables');

    if ($pvp_stored_variables) {
      $order = $event->getOrder();
      $order->setData('utm_codes', unserialize($pvp_stored_variables));
    }
  }

}