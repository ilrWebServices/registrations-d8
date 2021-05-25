<?php

namespace Drupal\ilr_registrations\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Url;

/**
 * Creates a catalog menu link from an environment variable.
 */
class DisplayCatalogMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getUrlObject($title_attribute = TRUE) {
    $marketing_url = (getenv('MARKETING_SITE_HOSTNAME'))
      ? getenv('MARKETING_SITE_HOSTNAME')
      : 'https://www.ilr.cornell.edu';

    return Url::fromUri($marketing_url . '/programs/professional-education');
  }

}
