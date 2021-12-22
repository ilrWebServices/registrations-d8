<?php

namespace Drupal\commerce_cardconnect_hpp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

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
 *   payment_type = "payment_cardpointe_hpp",
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
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * A shared tempstore for `cardpointe_hpp`.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $tempstoreShared;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time')
    );
    $instance->logger = $container->get('logger.factory')->get('commerce_cardpointe_hpp');
    $instance->tempstoreShared = $container->get('tempstore.shared')->get('cardpointe_hpp');
    return $instance;
  }

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
      '#description' => $this->t('Your merchant ID will be used to verify completed payments.'),
    ];

    $form['pay_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Pay link'),
      '#default_value' => $this->configuration['pay_link'],
      '#required' => TRUE,
      '#description' => $this->t('Enter the Pay Link custom URL for your hosted payment page here.'),
    ];

    $form['mini'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mini form'),
      '#default_value' => $this->configuration['mini'],
      '#description' => $this->t("Use the mini form to request the customer's payment card data without the billing information."),
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
    $json = $request->get('json');
    $error_messages = [];

    if ($json) {
      $this->logger->info('Webhook json data received: @data', [
        '@data' => $json,
      ]);

      $data = json_decode($json, TRUE);
    }
    else {
      $error_messages[] = 'Missing `json` parameter.';
      $data = [];
    }

    if (empty($data['merchantId']) || $data['merchantId'] !== $this->configuration['merchant_id']) {
      $error_messages[] = 'Missing or invalid MID.';
    }

    if (empty($data['invoice'])) {
      $error_messages[] = 'Missing invoice.';
    }

    // Load the order.
    $order = $this->entityTypeManager->getStorage('commerce_order')->loadByProperties([
      'order_id' => $data['invoice'],
    ]);

    if ($order) {
      $order = reset($order);
    }
    else {
      $error_messages[] = 'No such order.';
    }

    if ($error_messages) {
      $error_messages_string = implode("\t", $error_messages);

      $this->logger->error('Webhook data error: @error', [
        '@error' => $error_messages_string,
      ]);

      return new JsonResponse(['error' => $error_messages_string], 500);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'amount' => new Price($data['total'], 'USD'),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $data['gatewayTransactionId'],
      'authorized' => $this->time->getRequestTime(),
      'data' => $json,
    ]);

    if ($data['responseText'] === 'Approval') {
      $payment->set('state', 'completed');
    }
    else {
      $payment->set('state', 'authorization');
    }

    $payment->save();

    $this->logger->info('Payment created for order @order_id.', [
      '@order_id' => $order->id(),
    ]);

    // Add a record of this payment for this user to the shared tempstore. The
    // commerce_cardconnect_hpp.cardpointe_hpp.payment_return route will be able
    // to use this information to redirect a user returning from the hosted
    // payment page to the completed order.
    $this->tempstoreShared->set('cardpointe_hpp_transaction_id_for_user:' . $order->getCustomer()->id(), $data['gatewayTransactionId']);

    return new JsonResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Ensure that the order is paid in full with the cardpointe_hpp gateway.
    if (!$order->isPaid()) {
      // Throw a PaymentGatewayException if the order is not fully paid (a
      // payment should have been created in onNotify()).
      throw new PaymentGatewayException('Order ID ' . $order->id() . ' not completed because it is not paid in full.');
    }
  }

}
