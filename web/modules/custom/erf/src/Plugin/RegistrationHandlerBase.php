<?php

namespace Drupal\erf\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for RegistrationHandler plugin plugins.
 */
abstract class RegistrationHandlerBase extends PluginBase implements RegistrationHandlerInterface {

  use StringTranslationTrait;

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

  /**
   * {@inheritdoc}
   */
  public function getFormatterSettings() {
    return [];
  }

}
