<?php

/**
 * Implements hook_registrations_views_data_alter().
 */
function ilr_registrations_views_data_alter(array &$data) {
  // Alter the filter for the commerce order data column to a string filter.
  // This isn't really appropriate for a 'map' field type, but it gets the job
  // done for limiting results in a basic way.
  $data['commerce_order']['data']['filter']['id'] = 'string';
}
