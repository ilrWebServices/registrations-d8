<?php

namespace Drupal\commerce_cardconnect_hpp\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class HppCheckoutForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_freedompay\Plugin\Commerce\PaymentGateway\FreedomPayHPP $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    $data = [
      'total' => $order->getBalance()->getNumber(),
      'invoice' => $order->id(),
    ];

    if ($payment_gateway_configuration['mini']) {
      $data['mini'] = 1;
    }

    return $this->buildRedirectForm(
      $form,
      $form_state,
      $payment_gateway_configuration['pay_link'],
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );
  }

}
