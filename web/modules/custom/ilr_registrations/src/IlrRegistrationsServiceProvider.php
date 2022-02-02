<?php

namespace Drupal\ilr_registrations;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\StackMiddleware\NegotiationMiddleware;

/**
 * Modifies various services (with caution).
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

    // Register the CSV mime type in http_middleware.negotiation.
    if ($container->has('http_middleware.negotiation') && is_a($container->getDefinition('http_middleware.negotiation')->getClass(), NegotiationMiddleware::class, TRUE)) {
      $container->getDefinition('http_middleware.negotiation')->addMethodCall('registerFormat', ['csv', ['text/csv']]);
    }
  }

}
