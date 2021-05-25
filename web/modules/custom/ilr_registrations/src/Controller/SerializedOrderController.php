<?php

namespace Drupal\ilr_registrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ilr_registrations\SerializedOrderManagerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller used to return a JSON serialized commerce order.
 */
class SerializedOrderController extends ControllerBase {

  /**
   * Drupal\ilr_registrations\SerializedOrderManagerInterface definition.
   *
   * @var \Drupal\ilr_registrations\SerializedOrderManagerInterface
   */
  protected $serializedOrderManager;

  /**
   * Constructs a new SerializedOrderController object.
   */
  public function __construct(SerializedOrderManagerInterface $serialized_order_manager) {
    $this->serializedOrderManager = $serialized_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ilr_registrations.serialized_order')
    );
  }

  /**
   * Get a full serialized order object and return as JSON.
   *
   * @todo Refactor payment objects to allow for payment types other than credit
   * card.
   * @todo Verify hardcoded fields on the billing profile (e.g.
   * field_organization and the name fields).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A full serialized order object.
   */
  public function load(OrderInterface $commerce_order) {
    $serializable_order = $this->serializedOrderManager->getObjectForOrder($commerce_order);
    return new JsonResponse($serializable_order);
  }

}
