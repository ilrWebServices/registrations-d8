<?php

namespace Drupal\ilr_registrations\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Creates a dynamic username menu item for the current user.
 */
class DisplayNameMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return \Drupal::currentUser()->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return \Drupal::currentUser()->getDisplayName();
  }

}
