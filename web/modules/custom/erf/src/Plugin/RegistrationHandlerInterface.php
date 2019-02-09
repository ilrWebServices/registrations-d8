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

}
