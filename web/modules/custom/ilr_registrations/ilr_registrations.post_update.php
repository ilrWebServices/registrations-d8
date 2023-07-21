<?php

/**
 * @file
 * Contains ilr_registrations.post_update.php.
 */

use Drupal\commerce_price\Price;

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
    'price' => new Price('290', 'USD'),
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
    'title' => 'Groat Alpern Awards Ticket (\'12 - \'19 Graduate)',
    'sku' => 'groat-single-ten',
    'price' => new Price('100', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $single_ten_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Awards Ticket (\'12 - \'19 Graduate)',
    'stores' => [$store],
    'variations' => [$single_ten_variation],
    'field_registration_type' => 'groat_alpern_single',
  ])->save();

  // Recent grad single ticket.
  $single_recent_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => 'Groat Alpern Single Ticket (\'20 - \'21 Graduate)',
    'sku' => 'groat-single-recent',
    'price' => new Price('50', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $single_recent_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'groat_alpern_awards_ticket',
    'title' => 'Groat Alpern Single Ticket (\'20 - \'21 Graduate)',
    'stores' => [$store],
    'variations' => [$single_recent_variation],
    'field_registration_type' => 'groat_alpern_single',
  ])->save();

  // Sponsorships are a single product with multiple variations.
  $sponsor_two_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '2 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-2',
    'price' => new Price('1000', 'USD'),
    'field_ticket_limit' => 2,
  ]);
  $sponsor_two_variation->save();

  $sponsor_five_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '5 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-5',
    'price' => new Price('3000', 'USD'),
    'field_ticket_limit' => 5,
  ]);
  $sponsor_five_variation->save();

  $sponsor_ten_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '10 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-10',
    'price' => new Price('5000', 'USD'),
    'field_ticket_limit' => 10,
  ]);
  $sponsor_ten_variation->save();

  $sponsor_fifteen_variation = $product_variation_storage->create([
    'type' => 'groat_alpern_awards_ticket_level',
    'title' => '15 Ticket Sponsorship',
    'sku' => 'groat-sponsorship-15',
    'price' => new Price('7500', 'USD'),
    'field_ticket_limit' => 15,
  ]);
  $sponsor_fifteen_variation->save();

  $payable_check_term = \Drupal::service('entity.repository')->loadEntityByUuid('taxonomy_term', 'bdc560f0-3d85-42fe-9280-cad431cb032f');

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
    'field_tags' => [['target_id' => $payable_check_term->id()]],
  ])->save();
}

/**
 * Add 'Alumni' product_tags term.
 */
function ilr_registrations_post_update_01_alumni_product_tags_term(&$sandbox) {
  $term_entity_manager = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
  $term_entity_manager->create([
    'vid' => 'product_tags',
    'name' => 'Alumni',
    'uuid' => '577cd687-f818-4b38-92bb-57345eb1b93b',
  ])->save();
}

/**
 * Add initial Alumni Braves ticket products.
 */
function ilr_registrations_post_update_alumni_braves_tix_products(&$sandbox) {
  $store = \Drupal::service('entity_type.manager')->getStorage('commerce_store')->loadDefault();
  $product_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product');
  $product_variation_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_product_variation');
  $alumni_term = \Drupal::service('entity.repository')->loadEntityByUuid('taxonomy_term', '577cd687-f818-4b38-92bb-57345eb1b93b');

  $alum_tix_variation = $product_variation_storage->create([
    'type' => 'tickets_by_quantity',
    'title' => 'Alumni Braves Tickets for October 1, 2023',
    'sku' => 'ALUM-BRAVES-20231001',
    'price' => new Price('24', 'USD'),
  ]);
  $alum_tix_variation->save();

  $product_storage->create([
    'uid' => 1,
    'type' => 'tickets_by_quantity',
    'title' => 'Alumni Braves Tickets for October 1, 2023',
    'stores' => [$store],
    'variations' => [$alum_tix_variation],
    'field_tags' => [['target_id' => $alumni_term->id()]],
    'path' => '/alumni-atlanta-braves-2023',
    'body' => [
      'value' => 'Come join the Cornell ILR Alumni Association Atlanta Chapter for an afternoon at Truist Park to watch the Atlanta Braves take on the Washington Nationals!',
      'format' => 'plain_text',
    ],
  ])->save();
}
