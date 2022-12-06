<?php

namespace Drupal\ilr_registrations\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;

/**
 * Plugin implementation of the 'MapItem' formatter.
 *
 * @FieldFormatter(
 *   id = "map_dump",
 *   label = @Translation("Map dump"),
 *   field_types = {
 *     "map"
 *   }
 * )
 */
class MapItemFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays a "map" field.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $yaml = new Yaml;

    foreach ($items as $delta => $item) {
      if ($item instanceof MapItem) {
        $item_array = $item->toArray();

        // @todo Figure out how to deal with IlrOutreachDiscount objects.
        unset($item_array['ilr_outreach_discounts']);

        $element[$delta] = [
          // '#markup' => print_r($item->toArray(), TRUE),
          // '#markup' => json_encode($item->toArray(), JSON_PRETTY_PRINT),
          '#markup' => $yaml->encode($item_array),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ];
      }
    }

    return $element;
  }

}
