<?php

namespace Drupal\commerce_cardconnect_api\Plugin\Commerce\PaymentGateway;

use CardPointeGateway\CardPointeGatewayRestClient;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\AuthenticationException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

/**
 * Provides the CardPointe Gateway API payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "cardpointe_api",
 *   label = @Translation("CardPointe Gateway API"),
 *   display_label = @Translation("CardPointe Gateway API"),
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_cardconnect_api\PluginForm\CardPointeApiPaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa", "amex",
 *   }
 * )
 */
class CardPointeApi extends OnsitePaymentGatewayBase {

  public function defaultConfiguration() {
    return [
      'cp_user' => '',
      'cp_pass' => '',
      'cp_site' => 'fts',
      'cp_merchant_id' => '',
    ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['cp_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CardPointe API username'),
      '#default_value' => $this->configuration['cp_user'],
      '#required' => TRUE,
    ];

    $form['cp_pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CardPointe API password'),
      '#default_value' => $this->configuration['cp_pass'],
      '#required' => TRUE,
    ];

    $form['cp_site'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CardPointe API site'),
      '#description' => $this->t('See @link and your service configuration. Note: Do not append `-uat` here for testing. The test mode will do that automatically.', [
        '@link' => Link::fromTextAndUrl($this->t('the API Connectivity Guide'), Url::fromUri('https://developer.cardpointe.com/guides/api-connectivity#web-service-uRLs'))->toString(),
      ]),
      '#default_value' => $this->configuration['cp_site'],
      '#required' => TRUE,
    ];

    $form['cp_merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['cp_merchant_id'],
      '#required' => TRUE,
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['cp_user'] = $values['cp_user'];
    $this->configuration['cp_pass'] = $values['cp_pass'];
    $this->configuration['cp_site'] = $values['cp_site'];
    $this->configuration['cp_merchant_id'] = $values['cp_merchant_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    // gather all necessary information from the payment, payment method, and order
    $payment_method = $payment->getPaymentMethod();

    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();

    /** @var \\Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $payment_method->getBillingProfile();

    // Perform verifications, throwing exceptions as needed.
    $this->assertPaymentState($payment, ['new']);
    $this->assertPaymentMethod($payment_method);

    /** @var \Drupal\commerce_price\NumberFormatter $number_formatter */
    $number_formatter = \Drupal::service('commerce_price.number_formatter');

    // Perform the API request(s), throwing exceptions as needed.
    try {
      $is_live = $this->getMode() === 'live';
      $client = new CardPointeGatewayRestClient([
        'cp_user' => $is_live ? $this->configuration['cp_user'] : 'testing',
        'cp_pass' => $is_live ? $this->configuration['cp_pass'] : 'testing123',
        'cp_site' => $this->configuration['cp_site'] . ($is_live ? '' : '-uat'),
      ]);

      // The 'Payment process' pane settings determine the 'capture' value.
      $data = [
        'merchid' => $this->configuration['cp_merchant_id'],
        'amount' => $number_formatter->format($payment->getAmount()->getNumber(), ['minimum_fraction_digits' => 2]),
        'expiry' => $payment_method->card_exp_year->value . $payment_method->card_exp_month->value,
        'account' => $payment_method->remote_id->value,
        'orderid' => $order->id(),
        'capture' => $capture ? 'Y' : 'N',
        'email' => $order->getEmail(),
      ];

      if ($billing_profile) {
        /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
        $billing_address = $billing_profile->get('address')->first();

        $data['name'] = $billing_address->getGivenName() . ' ' . $billing_address->getFamilyName();
      }

      $client_response = $client->post('auth', ['json' => $data]);
    }
    // Could not connect to server or other network issue.
    catch (ConnectException $e) {
      throw new AuthenticationException('CardConnect API ' . $e->getMessage());
    }
    // 4xx error. This is either a 400 Bad Request (e.g. invalid syntax) or
    // 401 Unauthorized (e.g. bad credentials).
    catch (ClientException $e) {
      // print_r($e->getResponse()->getData());
      throw new AuthenticationException('CardConnect API ' . $e->getMessage());
    }
    // 5xx error. This is an 'Internal Server Error'.
    catch (ServerException $e) {
      // print_r($e->getResponse()->getData());
      throw new AuthenticationException('CardConnect API ' . $e->getMessage());
    }
    catch (\Exception $e) {
      throw new AuthenticationException('CardConnect API error: ' . $e->getMessage());

    }

    $response = $client_response->getData();

    // As far as we can tell, `respstat` is normalized between different credit
    // card processors, so A = Approval, B = Temporary processing issue, such as
    // a network error, and C = Rejection. See
    // https://developer.cardpointe.com/gateway-response-codes
    if ($response['respstat'] === 'C') {
      if ($response['resptext'] === 'Invalid token') {
        $this->messenger()->addError($this->t('Please check your card number.'));
      }

      throw new DeclineException('CardConnect API rejection response: ' . $response['resptext']);
    }
    elseif ($response['respstat'] === 'B') {
      throw new InvalidResponseException('CardConnect API processing issue: ' . $response['resptext']);
    }

    // Create and save information to a Commerce payment entity.
    $next_state = $capture ? 'completed' : 'authorization';

    $payment->setState($next_state);
    $payment->setRemoteId($response['retref']);
    $payment->setAuthorizedTime(\Drupal::time()->getRequestTime());
    $payment->setAvsResponseCode($response['avsresp']);

    if (!$payment_method->card_type->isEmpty()) {
      $avs_response_code_label = $this->buildAvsResponseCodeLabel($response['avsresp'], $payment_method->card_type->value);
      $payment->setAvsResponseCodeLabel($avs_response_code_label);
    }

    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $payment_method->save();

  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Delete the local entity.
    $payment_method->delete();
  }

}
