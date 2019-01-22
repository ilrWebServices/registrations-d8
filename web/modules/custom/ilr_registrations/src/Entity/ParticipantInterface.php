<?php

namespace Drupal\ilr_registrations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Participant entities.
 *
 * @ingroup ilr_registrations
 */
interface ParticipantInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Participant name.
   *
   * @return string
   *   Name of the Participant.
   */
  public function getName();

  /**
   * Sets the Participant name.
   *
   * @param string $name
   *   The Participant name.
   *
   * @return \Drupal\ilr_registrations\Entity\ParticipantInterface
   *   The called Participant entity.
   */
  public function setName($name);

  /**
   * Gets the Participant creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Participant.
   */
  public function getCreatedTime();

  /**
   * Sets the Participant creation timestamp.
   *
   * @param int $timestamp
   *   The Participant creation timestamp.
   *
   * @return \Drupal\ilr_registrations\Entity\ParticipantInterface
   *   The called Participant entity.
   */
  public function setCreatedTime($timestamp);

}
