<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce_mapping\Event\SalesforcePullEvent;

/**
 * Class SalesforceEventSubscriber.
 */
class SalesforceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      SalesforceEvents::PULL_PRESAVE => 'pullPresave',
    ];
    return $events;
  }

  /**
   * Pull presave event callback.
   *
   * @param \Drupal\salesforce_mapping\Event\SalesforcePullEvent $event
   *   The event.
   */
  public function pullPresave(SalesforcePullEvent $event) {
    // Modify commerce_promotions before they are saved after a SF pull.
    if ($event->getMapping()->id() === 'discount_promotion') {
      $commerce_promotion = $event->getEntity();
      $default_store = \Drupal::service('commerce_store.default_store_resolver')->resolve();

      // Ensure that this promotion has a store assigned. It can be missing if
      // the promotion is imported via salesforce.
      if (count($commerce_promotion->getStoreIds()) === 0 && $default_store) {
        // Set the default store for this storeless promotion.
        $commerce_promotion->setStoreIds([$default_store->id()]);
      }

      // Ensure that this promotion has at least one order type. It can be
      // missing if the promotion is imported via salesforce.
      if (count($commerce_promotion->getOrderTypeIds()) === 0 && $default_store) {
        // Set the order type for this typeless promotion.
        $commerce_promotion->setOrderTypeIds(['registration']);
      }
    }
  }

}
