<?php

namespace Drupal\erf\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Registration type entities.
 */
interface RegistrationTypeInterface extends ConfigEntityInterface {

  /**
   * Return the configured handlers for this registration type.
   *
   * @return Array
   *   An array of registration handler plugin machine names.
   */
  public function getHandlers();

}
