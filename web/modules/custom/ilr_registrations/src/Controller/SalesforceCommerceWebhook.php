<?php

namespace Drupal\ilr_registrations\Controller;

use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Response;

/**
 * Class SalesforceCommerceWebhook.
 */
class SalesforceCommerceWebhook extends ControllerBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Drupal queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a new SalesforceCommerceWebhook object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The logger channel factory.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->logger = $this->getLogger('ilr_registrations_webhook');
    $this->queue = $queue_factory->get('salesforce_commerce_webhook_processor');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue')
    );
  }

  /**
   * Receive and queue incoming salesforce-commerce webhook requests.
   *
   * @todo Add some kind of authentication or verification.
   *
   * @todo Validate the payload.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request with the salesforce mapping payload.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function v1(Request $request) {
    $queue_item_id = $this->queue->createItem(json_decode($request->getContent(), TRUE));

    return new JsonResponse([
      'status' => JsonResponse::HTTP_OK,
      'message' => 'Payload item ' . $queue_item_id . ' queued for processing.',
    ], JsonResponse::HTTP_OK);
  }

}
