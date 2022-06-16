<?php

namespace Drupal\commerce_payment_type_extra\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides payment type with an extra data field.
 *
 * @CommercePaymentType(
 *   id = "payment_extra",
 *   label = @Translation("Extra"),
 * )
 */
class PaymentExtra extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields['data'] = BundleFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    return $fields;
  }

}
