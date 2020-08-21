<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\ilr_registrations\SerializedOrderManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
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
   * A logger instance.
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
   * Constructs a new OrderCompleteSubscriber object.
   *
   * @param \Drupal\ilr_registrations\SerializedOrderManagerInterface $serialized_order_manager
   *   The serialized order manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The logger channel factory.
   */
  public function __construct(SerializedOrderManagerInterface $serialized_order_manager, LoggerChannelFactoryInterface $logger_factory, QueueFactory $queue_factory) {
    $this->serializedOrderManager = $serialized_order_manager;
    $this->logger = $logger_factory->get('ilr_registrations_webhook');
    $this->queue = $queue_factory->get('commerce_order_to_salesforce');
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

    // Queue the serialized order for submission to the WebReg webhook on
    // Salesforce.
    $queue_item_id = $this->queue->createItem($serialized_order);

    $this->logger->notice('Outgoing Salesforce WebReg webhook request queued for order @order_id', [
      '@order_id' => $order->id(),
    ]);
  }

}
