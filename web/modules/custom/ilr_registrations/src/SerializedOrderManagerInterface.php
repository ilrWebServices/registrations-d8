<?php

namespace Drupal\ilr_registrations;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface SerializedOrderManagerInterface.
 */
interface SerializedOrderManagerInterface {

  /**
   * Get a giant order object with lots of stuff.
   *
   * Stuff such as payments, discounts, customer info, mapped salesforce ids,
   * and attached registration info.
   *
   * @return array An array that follows the schema from
   * https://github.com/ilrWebServices/ilr-salesforce-bridge-api/blob/master/salesforce-webhook-request-schema.json
   */
  public function getObjectForOrder(OrderInterface $order);

}
