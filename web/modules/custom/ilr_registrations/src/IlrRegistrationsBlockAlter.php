<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a trusted callback to alter the commerce cart block.
 *
 * @see ilr_registrations_block_view_commerce_cart_alter()
 */
class IlrRegistrationsBlockAlter implements RenderCallbackInterface {

  /**
   * #pre_render callback: Updates the cart icon.
   */
  public static function preRender($build) {
    $build['content']['#icon']['#uri'] = "modules/contrib/commerce/icons/bebebe/cart.png";
    return $build;
  }

}
