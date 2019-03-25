<?php

namespace Drupal\commerce_freedompay\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;

/**
 * Checks for access to payment responses by `transid` from the Freedompay HPP.
 */
class HPPResponseTransidAccess implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an HPPResponseTransidAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access for requests to this controller.
   *
   * @see commerce_freedompay.routing.yml
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, Request $request) {
    $transid = $request->query->get('transid');

    // Ensure that there is a `transid` query parameter.
    if (!$transid) {
      return AccessResult::forbidden();
    }

    // Ensure that the `transid` parameter is valid.
    if (strlen($transid) !== 36) {
      return AccessResult::forbidden();
    }

    // Ensure that the `transid` parameter is a pending payment.
    $payment = \Drupal::entityTypeManager()->getStorage('commerce_payment')->loadByProperties([
      'remote_id' => $transid,
      'state' => 'new',
    ]);

    if (!$payment) {
      return AccessResult::forbidden();
    }

    // Ensure that the payment is for an order owned by the current user.
    $payment = reset($payment);
    if ($payment->order_id->entity->uid->entity->id() !== $account->id()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
