<?php

namespace Drupal\ilr_groat\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'integer_year' formatter.
 *
 * @FieldFormatter(
 *   id = "integer_year",
 *   label = @Translation("Integer year"),
 *   description = @Translation("Display a four digit integer as a two digit year."),
 *   field_types = {
 *     "integer",
 *     "list_integer",
 *   }
 * )
 */
class IntegerYearFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $value = strlen($item->value) === 4 ? substr($item->value, -2) : $item->value;
      $elements[$delta] = [
        'test' => [
          '#markup' => $value,
          '#prefix' => "'",
        ],
      ];
    }

    return $elements;
  }

}
