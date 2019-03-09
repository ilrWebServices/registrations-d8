<?php

namespace Drupal\commerce_freedompay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the FreedomPay HPP (Hosted Payment Page) payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "freedompay_hpp",
 *   label = @Translation("FreedomPay HPP (Hosted Payment Page)"),
 *   display_label = @Translation("FreedomPay HPP"),
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "amex",
 *   },
 * )
 */
class FreedomPayHPP extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'store_id' => '',
        'terminal_id' => '',
      ] + parent::defaultConfiguration();
  }

    /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store ID'),
      '#default_value' => $this->configuration['store_id'],
      '#required' => TRUE,
    ];
    $form['terminal_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Terminal ID'),
      '#default_value' => $this->configuration['terminal_id'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['store_id'] = $values['store_id'];
      $this->configuration['terminal_id'] = $values['terminal_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    if ($this->getMode() === 'test') {
      return 'https://payments.uat.freedompay.com/checkoutservice/checkoutservice.svc';
    }
    else {
      return 'https://payments.freedompay.com/checkoutservice/checkoutservice.svc';
    }
  }

}
