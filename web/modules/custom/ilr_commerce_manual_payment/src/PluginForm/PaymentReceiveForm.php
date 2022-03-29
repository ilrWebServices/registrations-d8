<?php

namespace Drupal\ilr_commerce_manual_payment\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentReceiveForm as CommercePaymentReceiveForm;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentReceiveForm extends CommercePaymentReceiveForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['remote_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote ID'),
      '#description' => $this->t('The Remote ID can be used to store useful information about the payment, such as the check number.'),
      '#default_value' => $payment->getRemoteId(),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $amount = Price::fromArray($values['amount']);
    $remote_id = $values['remote_id'];
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    $payment_gateway_plugin->receivePayment($payment, $amount, $remote_id);
  }

}
