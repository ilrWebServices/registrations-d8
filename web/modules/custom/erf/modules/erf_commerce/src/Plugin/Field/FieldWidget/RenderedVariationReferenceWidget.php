<?php

namespace Drupal\erf_commerce\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcherInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\FilterVariationsEvent;

/**
 * A field widget that displays commerce_product_variations as a list of
 * selectable, rendered entities.
 *
 * @FieldWidget(
 *   id = "rendered_variations",
 *   label = @Translation("Rendered Variations"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class RenderedVariationReferenceWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * View modes available for the product variation display and selection.
   *
   * @var array
   */
  protected $variationViewModes;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a RegistrationFormFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, $entity_type_manager, $event_dispatcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->variationViewModes = [];
    foreach ($entity_display_repository->getViewModes('commerce_product_variation') as $mode_name => $mode) {
      $this->variationViewModes[$mode_name] = $mode['label'];
    }

    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'variation_view_mode' => 'cart',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['variation_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Variation view mode'),
      '#options' => $this->variationViewModes,
      '#default_value' => $this->getSetting('variation_view_mode'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Rendered variations will be displayed using the %mode view mode.', [
      '%mode' => $this->variationViewModes[$this->getSetting('variation_view_mode')],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element += [
      '#type' => 'commerce_product_variations_entity_selector',
      '#variations' => $this->getRenderedVariationOptions($items->getEntity()),
      '#view_mode' => $this->getSetting('variation_view_mode'),
      '#default_value' => $this->getSelectedOptions($items),
    ];

    return $element;
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getRenderedVariationOptions(FieldableEntityInterface $entity) {
    if (!isset($this->options)) {
      $standalone_registration = $entity->entity_id->isEmpty();
      $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');

      // Limit the settable options for the current user account.
      $enabled_variations = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->column, $entity)
        ->getSettableOptions(\Drupal::currentUser());

      // Set the values of the options to full product variation entities.
      $enabled_variations = $variation_storage->loadMultiple(array_keys($enabled_variations));

      // Use the same variation filter as ProductVariationStorage->loadEnabled()
      // to further limit the options.
      $source_entity = $entity->getSourceEntity();
      if ($source_entity instanceof \Drupal\commerce_product\Entity\Product) {
        $event = new FilterVariationsEvent($source_entity, $enabled_variations);
        $this->eventDispatcher->dispatch(ProductEvents::FILTER_VARIATIONS, $event);
        $enabled_variations = $event->getVariations();
      }

      $this->options = $enabled_variations;
    }

    return $this->options;
  }

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow this widget on our `product_variation` field.
    // @see config/install/field.storage.registration.product_variation.yml
    $storage_def = $field_definition->getFieldStorageDefinition();
    return $storage_def->getName() === 'product_variation';
  }

}
