<?php

namespace Drupal\ilr_registrations\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
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
   * A queue worker.
   *
   * @var \Drupal\Core\Queue\QueueWorkerInterface
   */
  protected $queueWorker;

  /**
   * A queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

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
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue worker manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(RestClientInterface $sfapi, QueueWorkerManagerInterface $queue_manager, QueueFactory $queue_factory) {
    $this->sfapi = $sfapi;
    $this->queueWorker = $queue_manager->createInstance('salesforce_commerce_webhook_processor');
    $this->queue = $queue_factory->get('salesforce_commerce_webhook_processor');
    $this->logger = $this->getLogger('ilr_registrations_webhook');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('salesforce.client'),
      $container->get('plugin.manager.queue_worker'),
      $container->get('queue')
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
      /** @var \Drupal\salesforce\Rest\RestResponse */
      $sf_response = $this->sfapi->apiCall('/services/apexrest/WebReg', $data, 'POST', TRUE);

      $this->logger->notice('WebReg hook success for order @order_id. Response code: @response_code. Response message: @response_message - Data: @response_data', [
        '@order_id' => $data['order_id'],
        '@response_code' => $sf_response->getStatusCode(),
        '@response_message' => $sf_response->getReasonPhrase(),
        '@response_data' => $sf_response->getBody()->getContents(),
      ]);
    }
    // Catch `RequestException`s first.
    catch (RestException $e) {
      $response = $e->getResponse();

      // If this was a connection error, throw a SuspendQueueException exception
      // so that this and any other queue items will be retried at the next cron
      // run. The exception will be logged.
      if ($response === NULL) {
        throw new SuspendQueueException($e->getMessage());
      }
      // If there was no connection error but there was some other error (e.g.,
      // the WebReg endpoint was not found or returned an error), log it and
      // throw an exception. This queue item will be retried next cron run.
      else {
        print_r($response->getHeader('content-type'));
        print_r($e->getResponseBody());
        $this->logger->error('WebReg hook rest error for order @order_id. Response code: @response_code. Response message: @response_message. Error message: @message', [
          '@order_id' => $data['order_id'],
          '@response_code' => $response->getStatusCode(),
          '@response_message' => $response->getReasonPhrase(),
          '@message' => $e->getMessage(),
        ]);

        throw new \Exception($e->getMessage());
      }
    }

    print_r($sf_response->getStatusCode());

    if ($sf_response->getStatusCode() == 200) {
      print_r($sf_response->getHeader('content-type'));
      print_r($sf_response);
      print_r($sf_response->getBody()->getContents());

      // Assume this is decoded JSON response. E.g. $data['sf_order_id'].
      $data = $sf_response->getBody()->getContents();

      // Process the response immediately, if we can.
      try {
        // Note that we have bypassed _adding_ this item to the queue. We're
        // just using its ability to process data here.
        // @see SalesforceCommerceWebhookProcessor::processItem().
        $this->queueWorker->processItem($data);
      }
      catch (\Exception $e) {
        // Since we can't process the item right away, add it to the queue for
        // later retries.
        $this->queue->createItem($data);
      }
    }
  }

}
