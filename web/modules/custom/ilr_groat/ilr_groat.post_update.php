<?php

/**
 * @file
 * Contains ilr_groat.post_update.php.
 */

/**
 * Implements hook_removed_post_updates().
 */
function ilr_groat_removed_post_updates() {
  return [
    'ilr_groat_post_update_remove_source_entity' => '9.4.0',
  ];
}
