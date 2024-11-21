<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\commerce_price\Price;
use Drupal\erf_commerce\Event\RegistrationOrderItemEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for adjusting custom debate prices.
 */
class CustomRegistrationPriceSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [
      RegistrationOrderItemEvent::EVENT_NAME => 'onRegistrationOrderItem',
    ];
    return $events;
  }

  /**
   * Modify the order item price for a registration if a custom amount exists.
   *
   * @param \Drupal\erf_commerce\Event\RegistrationOrderItemEvent $event
   *   The event.
   */
  public function onRegistrationOrderItem(RegistrationOrderItemEvent $event) {
    if ($event->orderItem->getData('unit_price_overrider') && $event->orderItem->getData('unit_price_overrider') !== 'ilr_registrations') {
      return;
    }

    if (!$event->registration->hasField('field_user_price')) {
      return;
    }

    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $user_price */
    $user_price = $event->registration->field_user_price->first();

    if ($user_price->toPrice()->greaterThan(new Price('0', 'USD'))) {
      $event->orderItem->setUnitPrice($user_price->toPrice(), TRUE);
      $event->orderItem->setData('unit_price_overrider', 'ilr_registrations');
    }
    else {
      $event->orderItem->set('overridden_unit_price', FALSE);
    }
  }

}
