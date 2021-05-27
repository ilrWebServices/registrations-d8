<?php

namespace Drupal\ilr_registrations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;

/**
 * Provides a 'MarketingLinkBlock' block.
 *
 * @Block(
 *  id = "marketing_link_block",
 *  admin_label = @Translation("Marketing link block"),
 * )
 */
class MarketingLinkBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new MarketingLinkBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentRouteMatch $current_route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'marketing_site_url' => 'http://www.ilr.cornell.edu',
      'link_text' => 'Return to course details.',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['marketing_site_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Marketing site url'),
      '#description' => $this->t('The url of the marketing site'),
      '#default_value' => $this->configuration['marketing_site_url'],
      '#weight' => '0',
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#description' => $this->t('The text for the link back to the marketing site'),
      '#default_value' => $this->configuration['link_text'],
      '#maxlength' => 256,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['marketing_site_url'] = $form_state->getValue('marketing_site_url');
    $this->configuration['link_text'] = $form_state->getValue('link_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $product = $this->currentRouteMatch->getParameter('commerce_product');

    if (!$product) {
      return $build;
    }

    $mapped_object_storage = $this->entityTypeManager->getStorage('salesforce_mapped_object');
    $mapped_objects = $mapped_object_storage->loadByProperties([
      'drupal_entity__target_id' => $product->id(),
      'salesforce_mapping' => 'course_product',
    ]);

    $mapped_object = reset($mapped_objects);

    if (!$mapped_object) {
      return $build;
    }

    $marketing_site_url = $this->configuration['marketing_site_url'];
    $marketing_site_url .= '/course/' . $mapped_object->sfid();

    $build['marketing_link_block']['content'] = [
      '#type' => 'link',
      '#title' => $this->configuration['link_text'],
      '#url' => Url::fromUri($marketing_site_url),
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];

    return $build;
  }

}
