<?php

namespace Drupal\commerce_cardconnect_api\PluginForm;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\PluginForm\PaymentMethodEditForm;
use Drupal\Core\Form\FormStateInterface;

class CardPointeApiPaymentMethodEditForm extends PaymentMethodEditForm {

  protected function buildCreditCardForm(PaymentMethodInterface $payment_method, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($payment_method, $form_state);

    // @todo Revisit when https://www.drupal.org/project/commerce/issues/2790533
    // or similar lands.
    $element['is_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Default'),
      '#default_value' => $payment_method->isDefault(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $is_default = $form_state->getValue(['payment_method', 'payment_details', 'is_default']);

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $default_payment_methods = $this->entityTypeManager->getStorage('commerce_payment_method')->loadByProperties([
      'uid' => $payment_method->getOwner(),
      'is_default' => 1,
    ]);

    foreach ($default_payment_methods as $default_payment_method) {
      if ($default_payment_method->id() === $payment_method->id()) {
        continue;
      }
      $default_payment_method->setDefault(FALSE);
      $default_payment_method->save();
    }

    $payment_method->setDefault($is_default);

    parent::submitConfigurationForm($form, $form_state);
  }

}
