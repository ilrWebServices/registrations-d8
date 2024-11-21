<?php

namespace Drupal\ilr_debate_camp;

use Drupal\commerce_price\Price;
use Drupal\erf_commerce\Event\RegistrationOrderItemEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber for adjusting custom debate prices.
 */
class DebateCampItemPriceSubscriber implements EventSubscriberInterface {

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
   * Calculate the order item price for debate camp registrations.
   *
   * The price is the base price of the variation along with the housing fee for
   * residential campers. This will calculate the total for all participants.
   *
   * ** Note ** This requires that the `participants_as_quantity` setting on the
   * registration type be unchecked.
   *
   * @param \Drupal\erf_commerce\Event\RegistrationOrderItemEvent $event The
   *   event.
   */
  public function onRegistrationOrderItem(RegistrationOrderItemEvent $event) {
    if ($event->orderItem->getData('unit_price_overrider') && $event->orderItem->getData('unit_price_overrider') !== 'ilr_debate_camp') {
      return;
    }

    $registration = $event->registration;
    $order_item = $event->orderItem;

    if (!$registration->getEntityTypeId() === 'international_summer_debate_camp') {
      return;
    }

    if ($registration->hasField('product_variation') && $registration->product_variation->entity->hasField('field_housing_price')) {
      $base_price = $registration->product_variation->entity->getPrice();
      $housing_price = $registration->product_variation->entity->field_housing_price->first()->toPrice();
    }
    else {
      return;
    }

    $total_price = new Price('0', 'USD');

    foreach ($registration->participants->referencedEntities() as $participant) {
      $total_price = $total_price->add($base_price);

      if ($participant->field_housing_type->value === 'residential') {
        $total_price = $total_price->add($housing_price);
      }
    }

    $order_item->setUnitPrice($total_price, TRUE);
    $order_item->setData('unit_price_overrider', 'ilr_debate_camp');
  }

}
