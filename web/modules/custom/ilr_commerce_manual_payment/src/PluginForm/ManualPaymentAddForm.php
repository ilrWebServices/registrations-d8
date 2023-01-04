<?php

namespace Drupal\ilr_commerce_manual_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\ManualPaymentAddForm as CommerceManualPaymentAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * A manual payment form with a remote_id field.
 */
class ManualPaymentAddForm extends CommerceManualPaymentAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['remote_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote ID'),
      '#description' => $this->t('The Remote ID can be used to store useful information about the payment, such as the check number or Cardpointe payment ID.'),
      '#states' => [
        'visible' => [
          ':input[name="payment[received]"]' => ['checked' => TRUE],
        ],
      ],
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment->amount = $values['amount'];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->createPayment($payment, $values['received'], $values['remote_id']);
  }

}
