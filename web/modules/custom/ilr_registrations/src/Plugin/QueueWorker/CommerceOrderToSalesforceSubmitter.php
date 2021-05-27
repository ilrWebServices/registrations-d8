<?php

namespace Drupal\ilr_registrations\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\salesforce\Rest\RestClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salesforce\Rest\RestException;

/**
 * A Queue worker that submits Commerce Orders to a Salesforce webhook.
 *
 * @QueueWorker(
 *   id = "commerce_order_to_salesforce",
 *   title = @Translation("Commerce Order to Salesforce Submitter"),
 *   cron = {"time" = 120}
 * )
 */
class CommerceOrderToSalesforceSubmitter extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;

  /**
   * The salesforce rest client.
   *
   * @var \Drupal\salesforce\Rest\RestClientInterface
   */
  protected $sfapi;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new SalesforceCommerceWebhookProcessor.
   *
   * @param \Drupal\salesforce\Rest\RestClientInterface $sfapi
   *   The salesforce rest client.
   */
  public function __construct(RestClientInterface $sfapi) {
    $this->sfapi = $sfapi;
    $this->logger = $this->getLogger('ilr_registrations_webhook');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('salesforce.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Use the Salesforce module Rest API to post the serialized order to their
    // webhook. This will call the correct endpoint automatically based on the
    // default authentication provider.
    try {
      $sf_response = $this->sfapi->apiCall('/services/apexrest/WebReg', $data, 'POST', TRUE);

      $this->logger->notice('WebReg hook success for order @order_id. Response code: @response_code. Response message: @response_message', [
        '@order_id' => $data['order_id'],
        '@response_code' => $sf_response->getStatusCode(),
        '@response_message' => $sf_response->getReasonPhrase(),
      ]);
    }
    // Catch `RequestException`s first.
    catch (RestException $e) {
      $response = $e->getResponse();

      // If this was a connection error, throw a generic exception so that this
      // item will be retried. The exception will be logged.
      if ($response === NULL) {
        throw new \Exception($e->getMessage());
      }
      // If there was no connection error but there was some other error (e.g.,
      // the WebReg endpoint was not found or returned an error), just log it
      // and return with no exception. This will remove this queue item so that
      // it will NOT be retried.
      else {
        $this->logger->error('WebReg hook rest error for order @order_id. Response code: @response_code. Response message: @response_message. Error message: @message', [
          '@order_id' => $data['order_id'],
          '@response_code' => $response->getStatusCode(),
          '@response_message' => $response->getReasonPhrase(),
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

}
