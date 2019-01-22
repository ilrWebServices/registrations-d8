<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Participant entity.
 *
 * @see \Drupal\ilr_registrations\Entity\Participant.
 */
class ParticipantAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ilr_registrations\Entity\ParticipantInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished participant entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published participant entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit participant entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete participant entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add participant entities');
  }

}
