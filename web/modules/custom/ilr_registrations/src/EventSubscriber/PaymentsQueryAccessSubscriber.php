<?php

namespace Drupal\ilr_registrations\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Database\Connection;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for Payment entity access.
 */
class PaymentsQueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The primary database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new PaymentsQueryAccessSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Database\Connection $database
   *   The primary database connection.
   */
  public function __construct(EntityTypeBundleInfoInterface $bundle_info, Connection $database) {
    $this->bundleInfo = $bundle_info;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.commerce_payment' => 'onQueryAccess',
    ];
  }

  /**
   * Modifies the access conditions for payments to limit by order type access.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The event.
   */
  public function onQueryAccess(QueryAccessEvent $event) {
    if ($event->getOperation() != 'view') {
      return;
    }

    $account = $event->getAccount();
    $conditions = $event->getConditions();

    if ($account->hasPermission('view commerce_order')) {
      // The user has full access, no conditions needed.
      return;
    }

    $bundles = array_keys($this->bundleInfo->getBundleInfo('commerce_order'));
    $bundles_with_any_permission = [];
    foreach ($bundles as $bundle) {
      if ($account->hasPermission("view $bundle commerce_order")) {
        $bundles_with_any_permission[] = $bundle;
      }
    }

    if (empty($bundles_with_any_permission)) {
      // Hack alert!
      $conditions->addCondition('order_id', 0);
      return;
    }

    $subquery = $this->database->select('commerce_order', 'o');
    $subquery->addExpression("o.order_id", 'order_id');
    $subquery->condition('o.type', $bundles_with_any_permission, 'IN');
    $conditions->addCondition('order_id', $subquery, 'IN');
  }

}
