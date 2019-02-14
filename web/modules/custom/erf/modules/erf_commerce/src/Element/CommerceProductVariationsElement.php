<?php

namespace Drupal\erf_commerce\Element;

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
 * - #variations: An array of `commerce_product_variations`. Like #options.
 * - #view_mode: The view mode to use to render the product variations.
 *
 * Usage:
 * @code
 * $form['variation'] = [
 *   '#type' => 'commerce_product_variations_entity_selector',
 *   '#title' => 'Available Classes',
 *   '#variations' => $variations,
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
      // An array of `commerce_product_variation` entities.
      '#variations' => NULL,

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

    foreach ($element['#variations'] as $key => $variation) {
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
