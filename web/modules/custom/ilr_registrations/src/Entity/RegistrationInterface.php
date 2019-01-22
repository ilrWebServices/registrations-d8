<?php

namespace Drupal\ilr_registrations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Registration entities.
 *
 * @ingroup ilr_registrations
 */
interface RegistrationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Registration name.
   *
   * @return string
   *   Name of the Registration.
   */
  public function getName();

  /**
   * Sets the Registration name.
   *
   * @param string $name
   *   The Registration name.
   *
   * @return \Drupal\ilr_registrations\Entity\RegistrationInterface
   *   The called Registration entity.
   */
  public function setName($name);

  /**
   * Gets the Registration creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Registration.
   */
  public function getCreatedTime();

  /**
   * Sets the Registration creation timestamp.
   *
   * @param int $timestamp
   *   The Registration creation timestamp.
   *
   * @return \Drupal\ilr_registrations\Entity\RegistrationInterface
   *   The called Registration entity.
   */
  public function setCreatedTime($timestamp);

}
