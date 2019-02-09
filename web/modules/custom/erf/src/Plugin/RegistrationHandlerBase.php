<?php

namespace Drupal\erf\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for RegistrationHandler plugin plugins.
 */
abstract class RegistrationHandlerBase extends PluginBase implements RegistrationHandlerInterface {


  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

}
