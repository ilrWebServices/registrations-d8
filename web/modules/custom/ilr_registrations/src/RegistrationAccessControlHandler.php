<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Registration entity.
 *
 * @see \Drupal\ilr_registrations\Entity\Registration.
 */
class RegistrationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ilr_registrations\Entity\RegistrationInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view registrations');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit registrations');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete registrations');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add registrations');
  }

}
