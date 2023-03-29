<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\FilterVariationsEvent;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\ilr_registrations\EventSubscriber
 */
class CommerceEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new CommerceEventSubscriber object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(MessengerInterface $messenger, TranslationInterface $string_translation) {
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

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
      CartEvents::CART_ENTITY_ADD => 'displayReturnToCatalogMessage',
    ];
  }

  /**
   * React to a list of classes for a course product.
   *
   * This affects the display before they can be added to a cart.
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
        // Note: Watch out for timezones, DST, and other gotchas.
        $start_datetime = $variation->field_class_start->first()->get('value')->getDateTime();
        $tomorrow = new DrupalDateTime('tomorrow');

        // Check if the class has a close registration date/time.
        if (!$variation->field_close_registration->isEmpty()) {
          $current_datetime = new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE);
          $cutoff_date = $variation->field_close_registration->first()->get('value')->getDateTime();

          if ($cutoff_date < $current_datetime) {
            $display = FALSE;
          }
        }

        // If the start date is today or in the past, don't display it.
        if ($start_datetime < $tomorrow) {
          $display = FALSE;
        }

        // If a class is cancelled, do not display it.
        if ($variation->field_cancelled->value) {
          $display = FALSE;
        }

        // If a class is full, do not display it.
        if ($variation->field_full->value) {
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
   * https://docs.drupalcommerce.org/commerce2/developer-guide/customers/addresses/address-formats.
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

  /**
   * Displays a return to catalog message.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The add to cart event.
   */
  public function displayReturnToCatalogMessage(CartEntityAddEvent $event) {
    if ($event->getCart()->type->target_id !== 'registration') {
      return;
    }

    $marketing_url = (getenv('MARKETING_SITE_HOSTNAME'))
      ? getenv('MARKETING_SITE_HOSTNAME')
      : 'https://www.ilr.cornell.edu';
    $this->messenger->addMessage($this->t('Return to the <a href=":url">course catalog</a>.', [
      ':url' => $marketing_url . '/programs/professional-education',
    ]));
  }

}
