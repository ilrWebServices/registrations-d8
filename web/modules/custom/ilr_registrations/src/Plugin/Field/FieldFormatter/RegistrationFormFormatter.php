<?php

namespace Drupal\ilr_registrations\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

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
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->variationViewModes = [];
    foreach ($entity_display_repository->getViewModes('commerce_product_variation') as $mode_name => $mode) {
      $this->variationViewModes[$mode_name] = $mode['label'];
    }
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
      $container->get('entity_display.repository')
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
    $summary[] = $this->t('A registration entity form of the selected type will be shown along with a product variation selector. Upon submission, the variation will be added to the cart and a new registration will be created and linked to the order item.');
    $summary[] = $this->t('Product variations will be displayed using the %mode view mode.', [
      '%mode' => $this->variationViewModes[$this->getSetting('variation_view_mode')],
    ]);

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

    $product = $items->getEntity();
    $registration_type = $items->first()->target_id; // There can be only one!

    // Load a custom form that will combine the registration entity form
    // with some custom form elements for class product variation selection.
    $form = \Drupal::formBuilder()->getForm('Drupal\ilr_registrations\Form\RegisterCourseForm', $product, $registration_type, $this);

    $elements[0]['registration_add_form'] = $form;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_product' && $field_name == 'registration_type';
  }

}
