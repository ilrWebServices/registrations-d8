<?php

namespace Drupal\commerce_cardconnect_api\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides payment type with an extra data field.
 *
 * @CommercePaymentType(
 *   id = "payment_cardpointe",
 *   label = @Translation("Cardpointe"),
 * )
 */
class PaymentCardpointe extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields['authcode'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Authorization Code'))
      ->setDescription(t('Authorization Code from the Issuer.'))
      ->setSetting('max_length', 6);

    return $fields;
  }

}
