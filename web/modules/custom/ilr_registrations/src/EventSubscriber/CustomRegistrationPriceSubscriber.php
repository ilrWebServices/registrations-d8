<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\commerce_price\Price;
use Drupal\erf_commerce\Event\RegistrationOrderItemEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomRegistrationPriceSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      RegistrationOrderItemEvent::EVENT_NAME => 'onRegistrationOrderItem',
    ];
    return $events;
  }

  /**
   * Modify the order item price for a registration if a custom amount exists.
   *
   * @param RegistrationOrderItemEvent $event
   */
  public function onRegistrationOrderItem(RegistrationOrderItemEvent $event) {
    if (!$event->registration->hasField('field_user_price')) {
      return;
    }

    /** @var \Drupal\commerce_price\Plugin\Field\FieldType\PriceItem $user_price */
    $user_price = $event->registration->field_user_price->first();

    if ($user_price) {
      $user_price_value = $user_price->getValue();
      $unit_price = new Price($user_price_value['number'], $user_price_value['currency_code']);
      $event->orderItem->setUnitPrice($unit_price, TRUE);
    }
    else {
      $event->orderItem->set('overridden_unit_price', FALSE);
    }
  }

}
