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

}
