<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ilr_registrations\SerializedOrderManagerInterface;
use Drupal\salesforce\Rest\RestClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\salesforce\Rest\RestException;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\ilr_registrations\EventSubscriber
 */
class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * The serialized order manager.
   *
   * @var \Drupal\ilr_registrations\SerializedOrderManagerInterface
   */
  protected $serializedOrderManager;

  /**
   * Rest client service.
   *
   * @var \Drupal\salesforce\Rest\RestClientInterface
   */
  protected $sfapi;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new OrderCompleteSubscriber object.
   *
   * @param \Drupal\ilr_registrations\SerializedOrderManagerInterface $serialized_order_manager
   *   The serialized order manager.
   * @param \Drupal\salesforce\Rest\RestClientInterface $sfapi
   *   The salesforce rest client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(SerializedOrderManagerInterface $serialized_order_manager, RestClientInterface $sfapi, LoggerChannelFactoryInterface $logger_factory) {
    $this->serializedOrderManager = $serialized_order_manager;
    $this->sfapi = $sfapi;
    $this->logger = $logger_factory->get('ilr_registrations_webhook');
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => 'onOrderPlace',
    ];
    return $events;
  }

  public function onOrderPlace(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $serialized_order = $this->serializedOrderManager->getObjectForOrder($order);

    // Use the Salesforce module Rest API to post this serialized order to their
    // webhook. This will call the correct endpoint automatically based on the
    // default authentication provider.
    try {
      $response = $this->sfapi->apiCall('/services/apexrest/WebReg', $serialized_order, 'POST', TRUE);

      $this->logger->notice('WebReg hook success for order @order_id. Response code: @response_code. Response message: @response_message', [
        '@order_id' => $order->id(),
        '@response_code' => $response->getStatusCode(),
        '@response_message' => $response->getReasonPhrase(),
      ]);
    }
    // Catch `RequestException`s first.
    catch (RestException $e) {
      $response = $e->getResponse();

      $this->logger->error('WebReg hook rest error for order @order_id. Response code: @response_code. Response message: @response_message. Error message: @message', [
        '@order_id' => $order->id(),
        '@response_code' => $response ? $response->getStatusCode() : '?',
        '@response_message' => $response ? $response->getReasonPhrase() : '?',
        '@message' => $e->getMessage(),
      ]);
    }
    // Another exception may be thrown, e.g. for a network error, missing
    // OAuth credentials, invalid params, etc.
    catch (\Exception $e) {
      $this->logger->error('WebReg hook error for order @order_id. Error message: @message', [
        '@order_id' => $order->id(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
