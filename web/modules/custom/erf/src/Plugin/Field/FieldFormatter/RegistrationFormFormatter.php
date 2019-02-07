<?php

namespace Drupal\erf\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Plugin implementation of the 'registration_form' formatter.
 *
 * @FieldFormatter(
 *   id = "registration_form",
 *   label = @Translation("Registration Form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class RegistrationFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * View modes available for the product variation display and selection.
   *
   * @var array
   */
  protected $variationViewModes;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->variationViewModes = [];
    foreach ($entity_display_repository->getViewModes('commerce_product_variation') as $mode_name => $mode) {
      $this->variationViewModes[$mode_name] = $mode['label'];
    }

    $this->formBuilder = $form_builder;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_display.repository'),
      $container->get('form_builder')
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

    // $form['variation_view_mode'] = [
    //   '#type' => 'select',
    //   '#title' => $this->t('Variation view mode'),
    //   '#options' => $this->variationViewModes,
    //   '#default_value' => $this->getSetting('variation_view_mode'),
    // ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('A registration entity form of the selected type will be displayed.');
    // $summary[] = $this->t('Product variations will be displayed using the %mode view mode.', [
    //   '%mode' => $this->variationViewModes[$this->getSetting('variation_view_mode')],
    // ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // If no registration type is selected, render nothing.
    if ($items->isEmpty()) {
      return $elements;
    }

    $source_entity = $items->getEntity();
    $registration_type = $items->first()->target_id; // There can be only one!

    // Load a custom form that will combine the registration entity form
    // with some optional custom form elements.
    $form = $this->formBuilder->getForm('Drupal\erf\Form\EntityRegistrationForm', $source_entity, $registration_type, $this);

    $elements[0]['registration_add_form'] = $form;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow on fields that reference `registration_type` config entities.
    $storage_def = $field_definition->getFieldStorageDefinition();
    return $storage_def->getSetting('target_type') == 'registration_type';
  }

}
