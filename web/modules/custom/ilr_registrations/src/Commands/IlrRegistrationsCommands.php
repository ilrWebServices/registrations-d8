<?php

namespace Drupal\ilr_registrations\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\ilr_registrations\SerializedOrderManagerInterface;
use Drush\Commands\DrushCommands;

class IlrRegistrationsCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The serialized order manager.
   *
   * @var \Drupal\ilr_registrations\SerializedOrderManagerInterface
   */
  protected $serializedOrderManager;

  /**
   * The Drupal queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Creates a new ILR Registrations commands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ilr_registrations\SerializedOrderManagerInterface $serialized_order_manager
   *   The serialized order manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SerializedOrderManagerInterface $serialized_order_manager, QueueFactory $queue_factory) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->serializedOrderManager = $serialized_order_manager;
    $this->queue = $queue_factory->get('commerce_order_to_salesforce');
  }

  /**
   * Queue an order to send to Salesforce.
   *
   * @command ilr_registrations:queue-order
   * @aliases ilrqo
   *
   * @param string $order_id
   *   An order id.
   */
  public function queueOrder($order_id) {
    $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);

    if (empty($order)) {
      $this->io()->error(dt('Order not found for ID @order_id.', [
        '@order_id' => $order_id,
      ]));

      return;
    }

    if ($order->bundle() === 'registration') {
      $serialized_order = $this->serializedOrderManager->getObjectForOrder($order);

      // Queue the serialized order for submission to the WebReg webhook on
      // Salesforce.
      $this->queue->createItem($serialized_order);


      $this->io()->success(dt('Outgoing Salesforce WebReg webhook request queued for order @order_id.', [
        '@order_id' => $order_id,
      ]));
    }
    else {
      $this->io()->error(dt('Order is not a `registration` type.'));
    }
  }

}
