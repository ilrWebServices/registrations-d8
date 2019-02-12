<?php

namespace Drupal\erf_commerce\Plugin\RegistrationHandler;

use Drupal\erf\Plugin\RegistrationHandlerBase;

/**
 * Class VariationOrderItem.
 *
 * @RegistrationHandler(
 *   id = "erf_commerce_product",
 *   label = "Commerce Product Integration",
 *   description = "Registration forms attached to Commerce Products will include a product variation selection, add variations to new or existing carts, and automatically link order items to registrations.",
 *   source_entities = {
 *     "commerce_product"
 *   }
 * )
 */
class VariationOrderItem extends RegistrationHandlerBase {

  public function getFormatterSettings() {
    return [
      'variation_view_mode' => [
        'default_value' => 'cart',
        'form_element' => [
          '#type' => 'select',
          '#title' => $this->t('Variation view mode'),
          // '#options' => $this->variationViewModes,
          '#options' => ['test' => 'Test', 'foo' => 'Foo'],
        ],
        'summary' => 'Product variation view mode: %setting_value'
      ],
      'test' => [
        'default_value' => 'cart',
        'form_element' => [
          '#type' => 'textfield',
          '#title' => $this->t('Variation view mode'),
        ],
        'summary' => 'Test setting: %setting_value'
      ],
    ];
  }

}
