<?php
/**
 * @file
 * Contains \Drupal\erf\Annotation.
 */

namespace Drupal\erf\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RegistrationHandler item annotation object.
 *
 * Plugin Namespace: Plugin\RegistrationHandler
 *
 * @Annotation
 */
class RegistrationHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human readable name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   *
   * @var string
   */
  public $description;

  /**
   * The source entities that the handler can be used on. If empty, the handler
   * can be used by registrations attached to any source entity.
   *
   * @var array
   */
  public $source_entities = [];

}
