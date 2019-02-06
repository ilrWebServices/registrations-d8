<?php

namespace Drupal\ilr_registrations\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element\CompositeFormElementTrait;
use Drupal\Component\Utility\Html as HtmlUtility;

/**
 * Provides a radio-button based listing of rendered product variations for a
 * given product.
 *
 * Properties:
 * - #product: A product entity.
 * - #view_mode: The view mode to use to render the product variations.
 *
 * Usage:
 * @code
 * $form['variation'] = [
 *   '#type' => 'commerce_product_variations_entity_selector',
 *   '#title' => 'Available Classes',
 *   '#product' => $product_entity or $product_id,
 *   '#view_mode' => 'cart',
 *   '#default_value' => 1345,
 *   '#required' => TRUE,
 * ];
 * @endcode
 *
 * @FormElement("commerce_product_variations_entity_selector")
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21FormElement.php/class/FormElement
 * @see \Drupal\Core\Render\Element\RenderElement
 * @see
 * https://api.drupal.org/api/drupal/namespace/Drupal%21Core%21Render%21Element
 */
class CommerceProductVariationsElement extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      // Product entity from which to fetch the variations.
      '#product' => NULL,

      // The view mode to use to display the product variations.
      '#view_mode' => 'cart',

      '#input' => TRUE,
      '#process' => [
        [$class, 'processCommerceProductVariationsElement'],
      ],
      '#theme_wrappers' => ['container'],
      '#pre_render' => [
        [$class, 'preRenderCompositeFormElement'],
      ],
    ];
  }

  /**
   * Processes a 'commerce_product_variations_entity_selector' element.
   */
  public static function processCommerceProductVariationsElement(&$element, FormStateInterface $form_state, &$complete_form) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $view_builder = $entity_type_manager->getViewBuilder('commerce_product_variation');

    if ($element['#product'] instanceof \Drupal\commerce_product\Entity\Product) {
      $product = $element['#product'];
    }
    elseif (is_numeric($element['#product'])) {
      $product = $entity_type_manager->getStorage('commerce_product')->load($element['#product']);
    }

    $variations = $entity_type_manager->getStorage('commerce_product_variation')->loadEnabled($product);

    foreach ($variations as $key => $variation) {
      $parents_for_id = array_merge($element['#parents'], [$key]);

      $element[$key] = [
        '#type' => 'container',
        'rendered_entity' => $view_builder->view($variation, $element['#view_mode']),
      ];
      $element[$key]['radio'] = [
        '#type' => 'radio',
        '#title' => $variation->label(),
        '#return_value' => $key,
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
        '#parents' => $element['#parents'],
        '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
      ];
    }

    return $element;
  }

}
