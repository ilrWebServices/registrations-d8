<?php

namespace Drupal\ilr_registrations\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

class DisplayCopyrightMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return '© ' . date("Y") . ' Cornell University | ILR School';
  }

}
