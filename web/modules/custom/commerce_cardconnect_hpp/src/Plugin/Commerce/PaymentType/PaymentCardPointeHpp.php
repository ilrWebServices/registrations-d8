<?php

namespace Drupal\commerce_cardconnect_hpp\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeBase;
use Drupal\entity\BundleFieldDefinition;

/**
 * Provides the payment type for CardPointe HPP.
 *
 * @CommercePaymentType(
 *   id = "payment_cardpointe_hpp",
 *   label = @Translation("CardConnect CardPointe HPP"),
 * )
 */
class PaymentCardPointeHpp extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields['data'] = BundleFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('The serialized transaction data from the CardPointe HPP webhook'))
      ->setRequired(TRUE);

    return $fields;
  }

}
