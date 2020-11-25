<?php

namespace Drupal\commerce_cardconnect_hpp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\commerce_price\Price;

/**
 * Provides the CardPointe HPP offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "cardpointe_hpp",
 *   label = @Translation("CardPointe HPP (Hosted Payment Page)"),
 *   display_label = @Translation("CardPointe HPP"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_cardconnect_hpp\PluginForm\HppCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "amex",
 *   },
 *   modes = {
 *     "live" = "Live",
 *   }
 * )
 */
class CardPointeHPP extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'pay_link' => '',
      'mini' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
      '#description' => $this->t('Your merchant ID will be used to verify completed payments.')
    ];

    $form['pay_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Pay link'),
      '#default_value' => $this->configuration['pay_link'],
      '#required' => TRUE,
      '#description' => $this->t('Enter the Pay Link custom URL for your hosted payment page here.')
    ];

    $form['mini'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mini form'),
      '#default_value' => $this->configuration['mini'],
      '#description' => $this->t('Use the mini form to request the customer\'s payment card data without the billing information.')
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

      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['pay_link'] = $values['pay_link'];
      $this->configuration['mini'] = $values['mini'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $data = json_decode($request->getContent());
    $error_messages = [];

    if (empty($data->merchantId) || $data->merchantId !== $this->configuration['merchant_id']) {
      $error_messages[] = 'Missing or invalid MID.';
    }

    if (empty($data->invoice)) {
      $error_messages[] = 'Missing invoice.';
    }

    if ($error_messages) {
      return new JsonResponse(['error' => implode("\t", $error_messages)], 500);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => new Price($data->total, 'USD'),
      'payment_gateway' => $this->entityId,
      'order_id' => $data->invoice,
      'remote_id' => $data->token,
      'authorized' => $this->time->getRequestTime(),
    ]);
    $payment->set('state', 'completed');
    $payment->save();

    return new JsonResponse();
  }

}
