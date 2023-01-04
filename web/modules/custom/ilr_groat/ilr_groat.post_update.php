<?php

/**
 * @file
 * Contains ilr_groat.post_update.php.
 */

/**
 * Remove source entity for manually created groat tickets.
 */
function ilr_groat_post_update_remove_source_entity(&$sandbox) {
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $user_storage = $entity_type_manager->getStorage('user');
  $registration_storage = $entity_type_manager->getStorage('registration');

  $groat_admin_uids = $user_storage->getQuery()
    ->condition('roles', 'groat_alpern_admin', 'CONTAINS')
    ->execute();

  if (empty($groat_admin_uids)) {
    return;
  }

  $standalone_registration_query = $registration_storage->getQuery()
    ->condition('user_id', array_keys($groat_admin_uids), 'IN')
    ->condition('entity_type', 'commerce_product')
    ->notExists('commerce_order_item_id');

  $standalone_registration_ids = $standalone_registration_query->execute();

  if (empty($standalone_registration_ids)) {
    return;
  }

  $standalone_registrations = $registration_storage->loadMultiple(array_keys($standalone_registration_ids));

  foreach ($standalone_registrations as $standalone_registration) {
    $standalone_registration->entity_type = NULL;
    $standalone_registration->entity_id = NULL;
    $standalone_registration->save();
  }
}
