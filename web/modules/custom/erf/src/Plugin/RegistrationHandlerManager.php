<?php

namespace Drupal\erf\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the RegistrationHandlerManager plugin manager.
 */
class RegistrationHandlerManager extends DefaultPluginManager {

  /**
   * Default values for each plugin.
   *
   * @var array
   */
  protected $defaults = [
    'label' => '',
    'description' => '',
    'source_entities' => [],
  ];

  /**
   * Constructor for RegistrationHandlerManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend,  ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/RegistrationHandler',
      $namespaces,
      $module_handler,
      'Drupal\erf\Plugin\RegistrationHandlerInterface',
      'Drupal\erf\Annotation\RegistrationHandler');
    $this->setCacheBackend($cache_backend, 'registration_handler_plugins');
  }

}