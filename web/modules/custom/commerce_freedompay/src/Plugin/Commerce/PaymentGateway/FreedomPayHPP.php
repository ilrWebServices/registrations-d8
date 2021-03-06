<?php

namespace Drupal\commerce_freedompay\Plugin\Commerce\PaymentGateway;

use GuzzleHttp\Exception\ClientException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides the FreedomPay HPP (Hosted Payment Page) payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "freedompay_hpp",
 *   label = @Translation("FreedomPay HPP (Hosted Payment Page)"),
 *   display_label = @Translation("FreedomPay HPP"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_freedompay\PluginForm\HPPCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "amex",
 *   },
 * )
 */
class FreedomPayHPP extends OffsitePaymentGatewayBase {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle http client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory, ClientInterface $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->logger = $logger_channel_factory->get('commerce_freedompay');
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('logger.factory'),
      $container->get('http_client')
    );
  }

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
  public function onReturn(OrderInterface $order, Request $request) {
    // Get the transid from the redirect request.
    $transid = $request->query->get('transid');

    // If the `transid` query parameter was lost somewhere along the way, just
    // bail. It should be valid, however, since the user should have been
    // redirected here from HPPCallbackController::return().
    if (!$transid) {
      return;
    }

    // Load the payment associated with this returned transaction.
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->loadByProperties([
      'remote_id' => $transid,
      'order_id' => $order->id(),
      'state' => 'new',
    ]);
    $payment = reset($payment);

    // If the payment is missing, bail.
    if (!$payment) {
      return;
    }

    // Get the transaction from Freedompay.
    $hpp_transaction = $this->getTransaction($transid);

    if (!empty($hpp_transaction['AuthResponse']['AuthorizationDecision'])) {
      $payment->remote_state = $hpp_transaction['AuthResponse']['AuthorizationDecision'];

      if ($hpp_transaction['AuthResponse']['AuthorizationDecision'] === 'ACCEPT') {
        $payment->setState('completed');
      }

      $payment->save();
    }

    if ($payment->getState()->getId() !== 'completed') {
      // The AuthResponse.AuthorizationDecision was one of REJECT, FAILURE, or
      // ERROR, so throw a payment failure exception.
      // @todo Maybe delete the payment at this point? If the user tries to pay
      // again, a new transaction (and thus payment) will be created, so this
      // one is basically an orphan.
      throw new PaymentGatewayException('Payment failed!');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    // Call the parent onCancel method, which only sets a message.
    parent::onCancel($order, $request);

    // Get the transid from the redirect request.
    $transid = $request->query->get('transid');

    // If the `transid` query parameter was lost somewhere along the way, just
    // bail. It should be valid, however, since the user should have been
    // redirected here from HPPCallbackController::return().
    if (!$transid) {
      return;
    }

    // Load the payment associated with this canceled transaction.
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->loadByProperties([
      'remote_id' => $transid,
      'order_id' => $order->id(),
      'state' => 'new',
    ]);
    $payment = reset($payment);

    // If the payment is missing, bail.
    if (!$payment) {
      return;
    }

    // Delete the associated payment.
    $payment->delete();
  }

  /**
   * Return the URL to the payment API.
   *
   * @return string
   *   The URL to either the test or production payment service API.
   */
  public function getApiUrl() {
    if ($this->getMode() === 'test') {
      return 'https://payments.uat.freedompay.com/checkoutservice/checkoutservice.svc';
    }
    else {
      return 'https://payments.freedompay.com/checkoutservice/checkoutservice.svc';
    }
  }

  /**
   * Create a Freedompay HPP transaction.
   *
   * The returned transaction ID and payment form URL can be used to create and
   * complete a payment.
   *
   * @return array|false
   *   The reply object from Freedompay as an array or FALSE if there was an
   *   error. The array will contain the following keys:
   *   - CheckoutUrl: URL of HPP page
   *   - TransactionId: GUID string (36 chars) containing transaction ID
   *   - ResponseMessage: Error message string if an error occurs (Empty unless
   *     error)
   */
  public function createTransaction(OrderInterface $order) {
    $create_transaction_data = [
      'StoreId' => $this->configuration['store_id'],
      'TerminalId' => $this->configuration['terminal_id'],
      'TransactionTotal' => $order->getBalance()->getNumber(),
      'CaptureMode' => TRUE,
      'MerchantReferenceCode' => $order->id(),
    ];

    try {
      $response = $this->httpClient->post($this->getApiUrl() . '/createTransaction', [
        RequestOptions::JSON => $create_transaction_data,
      ]);
    }
    catch (ClientException $e) {
      $this->logger->alert('Could not create a transaction.');
      return FALSE;
    }

    $response_array = json_decode($response->getBody(), TRUE);

    if (!empty($response_array['ResponseMessage'])) {
      $this->logger->alert($response_array['ResponseMessage']);
      return FALSE;
    }

    return $response_array;
  }

  /**
   * Get a Freedompay HPP transaction via the API.
   *
   * The returned transaction ID and payment form URL can be used to create and
   * complete a payment.
   *
   * @return array|false
   *   The CheckoutDetailsResponse object from Freedompay as an array or FALSE
   *   if there was an error. The array will contain the following keys (among
   *   others):
   *   - OriginalRequest: Data from the original request
   *   - MaskedCardNumber: The masked card number on file associated with the
   *     token. Only populated if the transaction has successfully been
   *     authorized.
   *   - StoreName
   *   - AuthResponse
   *   - CaptureResponse:
   *   - FailedResponses:
   *   - TokenInformation:
   *   - ResponseMessage:
   *   - NameOnCard:
   */
  public function getTransaction($transid) {
    try {
      $response = $this->httpClient->post($this->getApiUrl() . '/getTransaction', [
        RequestOptions::JSON => $transid,
      ]);
    }
    catch (ClientException $e) {
      $this->logger->alert('Could not get a transaction.');
      return FALSE;
    }

    $response_array = json_decode($response->getBody(), TRUE);

    if (!empty($response_array['ResponseMessage'])) {
      $this->logger->alert($response_array['ResponseMessage']);
      return FALSE;
    }

    return $response_array;
  }

}
