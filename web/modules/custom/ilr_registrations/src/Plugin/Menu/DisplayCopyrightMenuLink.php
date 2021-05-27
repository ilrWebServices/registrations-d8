<?php

namespace Drupal\ilr_registrations\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Creates a dynamic copyright menu item from the current date.
 */
class DisplayCopyrightMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return '© ' . date("Y") . ' Cornell University | ILR School';
  }

}
