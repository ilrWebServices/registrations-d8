<?php

/**
 * @file
 * Contains ilr_registrations.post_update.php.
 */

/**
 * Add 'Pay by check' product term.
 */
function ilr_registrations_post_update_01_pay_by_check_term(&$sandbox) {
  $term_entity_manager = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
  $term_entity_manager->create([
    'vid' => 'product_tags',
    'name' => 'Payable by check',
    'uuid' => 'bdc560f0-3d85-42fe-9280-cad431cb032f',
  ])->save();
}

/**
 * Add initial Groat/Alpern products.
 */
function ilr_registrations_post_update_groat_products(&$sandbox) {
  $store = \Drupal::service('entity_type.manager')->getStorage('commerce_store')->loadDefault();
  $product_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product');
  $product_variation_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product_variation');

  // Each single ticket type is a separate product with a single variation, so
  // that multiple types can be purchased at the same time.
  // Full price single ticket.
  $single_full_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => 'Groat Alpern Single Ticket',
    'sku' => 'groat-single-full',
    'price' => new \Drupal\commerce_price\Price('290', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $single_full_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Single Ticket',
    'stores' => [$store],
    'variations' => [$single_full_variation],
    'field_registration_type' => 'groat_alpern_single',
  ])->save();

  // Ten year grad single ticket.
  $single_ten_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => 'Groat Alpern Single Ticket (ten years)',
    'sku' => 'groat-single-ten',
    'price' => new \Drupal\commerce_price\Price('100', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $single_ten_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Single Ticket (ten years)',
    'stores' => [$store],
    'variations' => [$single_ten_variation],
    'field_registration_type' => 'groat_alpern_single',
  ])->save();

  // Recent grad single ticket.
  $single_recent_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => 'Groat Alpern Single Ticket (recent)',
    'sku' => 'groat-single-recent',
    'price' => new \Drupal\commerce_price\Price('50', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $single_recent_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Single Ticket (recent)',
    'stores' => [$store],
    'variations' => [$single_recent_variation],
    'field_registration_type' => 'groat_alpern_single',
  ])->save();

  // Sponsorships are a single product with multiple variations.
  $sponsor_two_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '2 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-2',
    'price' => new \Drupal\commerce_price\Price('1000', 'USD'),
    'field_ticket_limit' => 2,
  ]);
  $sponsor_two_variation->save();

  $sponsor_five_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '5 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-5',
    'price' => new \Drupal\commerce_price\Price('3000', 'USD'),
    'field_ticket_limit' => 5,
  ]);
  $sponsor_five_variation->save();

  $sponsor_ten_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '10 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-10',
    'price' => new \Drupal\commerce_price\Price('5000', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $sponsor_ten_variation->save();

  $sponsor_fifteen_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '15 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-15',
    'price' => new \Drupal\commerce_price\Price('7500', 'USD'),
    'field_ticket_limit' => 15,
  ]);
  $sponsor_fifteen_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Sponsorship',
    'stores' => [$store],
    'variations' => [
      $sponsor_two_variation,
      $sponsor_five_variation,
      $sponsor_ten_variation,
      $sponsor_fifteen_variation,
    ],
    'field_registration_type' => 'groat_alpern_sponsorship',
  ])->save();
}
