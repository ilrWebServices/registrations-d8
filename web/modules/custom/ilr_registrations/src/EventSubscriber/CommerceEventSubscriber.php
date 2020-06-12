<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\ilr_registrations\EventSubscriber
 */
class CommerceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::FILTER_VARIATIONS => 'filterVariations',
      AddressEvents::ADDRESS_FORMAT => 'onAddressFormat',
    ];
  }

  /**
   * React to a list of classes for a course product before they can be added
   * to a cart.
   *
   * @param \Drupal\commerce_product\Event\FilterVariationsEvent $event
   *   Commerce product FilterVariationsEvent event.
   */
  public function filterVariations(FilterVariationsEvent $event) {
    $filtered_variations = [];

    foreach ($event->getVariations() as $key => $variation) {
      $display = TRUE;

      // Filter 'Class' product variations.
      if ($variation->bundle() === 'class') {
        // If a class has no end datetime, ignore it, which will display it.
        if ($variation->field_class_end->isEmpty()) {
          continue;
        }

        // Note: Watch out for timezones, DST, and other gotchas.
        $end_datetime = $variation->field_class_end->first()->get('value')->getDateTime();
        $current_datetime = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);

        // If the end date in the past, don't display it.
        if ($end_datetime < $current_datetime) {
          $display = FALSE;
        }
      }

      if ($display) {
        $filtered_variations[$key] = $variation;
      }
    }

    $event->setVariations($filtered_variations);
  }

  /**
   * Address format event callback.
   *
   * I would never in a million years have guessed how to display the middle
   * name field in address fields if not for this Commerce documentation:
   * https://docs.drupalcommerce.org/commerce2/developer-guide/customers/addresses/address-formats
   */
  public function onAddressFormat(AddressFormatEvent $event) {
    $definition = $event->getDefinition();

    // Include %additionalName (e.g. middle name) in the format.
    $format = $definition['format'];
    $format = str_replace('%additionalName', '', $format);
    $format = str_replace('%givenName %familyName', "%givenName %additionalName %familyName", $format);
    $definition['format'] = $format;
    $event->setDefinition($definition);
  }

}
