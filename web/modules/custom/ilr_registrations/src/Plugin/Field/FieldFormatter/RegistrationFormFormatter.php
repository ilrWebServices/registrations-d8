<?php

namespace Drupal\ilr_registrations\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

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
class RegistrationFormFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'combine' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // $form['combine'] = [
    //   '#type' => 'checkbox',
    //   '#title' => t('Combine order items containing the same product variation.'),
    //   '#description' => t('The order item type, referenced product variation, and data from fields exposed on the Add to Cart form must all match to combine.'),
    //   '#default_value' => $this->getSetting('combine'),
    // ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // if ($this->getSetting('combine')) {
    //   $summary[] = $this->t('Combine order items containing the same product variation.');
    // }
    // else {
    //   $summary[] = $this->t('Do not combine order items containing the same product variation.');
    // }

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

    // Load the default add Registration form.
    // $form = \Drupal::service('entity.form_builder')->getForm($registration, 'default');

    // Load a custom form that will combine the registration entity form
    // with some custom form elements for class product variation selection.
    $form = \Drupal::formBuilder()->getForm('Drupal\ilr_registrations\Form\RegisterCourseForm', $product, $registration_type);

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
