<?php

namespace Drupal\commerce_freedompay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class HPPCheckoutForm extends PaymentOffsiteForm {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_freedompay\Plugin\Commerce\PaymentGateway\FreedomPayHPP $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    // Create a freedompay transaction and get a payment form URL.
    $hpp_transaction = $payment_gateway_plugin->createTransaction($order);

    if (!$hpp_transaction) {
      return FALSE;
    }

    // Store the transaction id with the payment.
    $payment->remote_id = $hpp_transaction['TransactionId'];

    // Save the new payment. It will be completed or removed based on the
    // response from the offsite payment form.
    $payment->save();

    $data = [];

    return $this->buildRedirectForm(
      $form,
      $form_state,
      $hpp_transaction['CheckoutUrl'],
      $data,
      PaymentOffsiteForm::REDIRECT_GET
    );
  }
}
