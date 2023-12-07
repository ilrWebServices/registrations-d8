<?php

/**
 * @file
 * Contains ilr_registrations.post_update.php.
 */

/**
 * Implements hook_removed_post_updates().
 */
function ilr_registrations_removed_post_updates() {
  return [
    'ilr_registrations_post_update_01_pay_by_check_term' => '9.4.0',
    'ilr_registrations_post_update_groat_products' => '9.4.0',
    'ilr_registrations_post_update_01_alumni_product_tags_term' => '9.4.0',
    'ilr_registrations_post_update_alumni_braves_tix_products' => '9.4.0',
  ];
}
