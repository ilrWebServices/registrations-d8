<?php

namespace Drupal\ilr_registrations\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ilr_registrations\SerializedOrderManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides custom Drush commands for ILR registrations.
 */
final class IlrRegistrationsCommands extends DrushCommands {

  /**
   * Creates a new ILR Registrations commands object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly SerializedOrderManagerInterface $serializedOrderManager,
    private readonly QueueInterface $queue,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ilr_registrations.serialized_order'),
      $container->get('queue')->get('commerce_order_to_salesforce'),
    );
  }

  /**
   * Queue an order to send to Salesforce.
   */
  #[CLI\Command(name: 'ilr_registrations:queue-order', aliases: ['ilrqo'])]
  #[CLI\Argument(name: 'order_id', description: 'An order id.')]
  #[CLI\Usage(name: 'ilr_registrations:queue-order 5309', description: 'Queue order 5309 to send to Salesforce.')]
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
