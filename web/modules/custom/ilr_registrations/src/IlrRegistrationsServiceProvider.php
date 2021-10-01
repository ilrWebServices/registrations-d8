<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the email validator service.
 */
class IlrRegistrationsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides email.validator class to add DNS validation for email.
    if ($container->hasDefinition('email.validator')) {
      $definition = $container->getDefinition('email.validator');
      $definition->setClass('Drupal\ilr_registrations\EmailValidator');
    }
  }

}
