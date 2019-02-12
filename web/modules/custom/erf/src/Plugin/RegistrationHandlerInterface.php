<?php

namespace Drupal\erf\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface RegistrationHandlerInterface extends PluginInspectionInterface {

  /**
   * Get the plugin label.
   *
   * @return string
   *   The registration handler plugin label.
   */
  public function label();

  /**
   * Get the plugin description.
   *
   * @return string
   *   The registration handler plugin description.
   */
  public function description();

  /**
   * Get the settings info for the field formatter.
   *
   * @todo: Create a well-defined formatter settings object, rather than a magic array.
   *
   * @return array
   *   An array of setting definitions.
   */
  public function getFormatterSettings();

}
