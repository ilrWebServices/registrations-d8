<?php

/**
 * @file
 * Contains ilr_registrations.post_update.php.
 */

/**
 * Add 'Pay by check' product term.
 */
function ilr_registrations_post_update_pay_by_check_term(&$sandbox) {
  $term_entity_manager = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
  $term_entity_manager->create([
    'vid' => 'product_tags',
    'name' => 'Payable by check',
    'uuid' => 'bdc560f0-3d85-42fe-9280-cad431cb032f',
  ])->save();
}

