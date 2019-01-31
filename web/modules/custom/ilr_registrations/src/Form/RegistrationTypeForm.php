<?php

namespace Drupal\ilr_registrations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RegistrationTypeForm.
 */
class RegistrationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $registration_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $registration_type->label(),
      '#description' => $this->t("Label for the Registration type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $registration_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ilr_registrations\Entity\RegistrationType::load',
      ],
      '#disabled' => !$registration_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registration_type = $this->entity;
    $status = $registration_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Registration type.', [
          '%label' => $registration_type->label(),
        ]));

        $this->addParticipantsField();
        break;

      default:
        drupal_set_message($this->t('Saved the %label Registration type.', [
          '%label' => $registration_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($registration_type->toUrl('collection'));
  }

  /**
   * Adds the default participants field to a registration type.
   */
  private function addParticipantsField() {
    $field_storage = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('registration.field_participants');
    $field = \Drupal::entityTypeManager()->getStorage('field_config')->load('registration.' . $this->entity->id() . '.field_participants');

    if (empty($field)) {
      $field = \Drupal::entityTypeManager()->getStorage('field_config')->create([
        'field_storage' => $field_storage,
        'bundle' => $this->entity->id(),
        'label' => 'Participants',
        'settings' => [
          'handler' => 'default:participant',
          'handler_settings' => [
            'target_bundles' => [
              'default' => 'default', // @todo: Consider setting this on the Registration type form.
            ],
            'sort' => [
              'field' => '_none'
            ],
            'auto_create' => 'false',
            'auto_create_bundle' => '',
          ]
        ],
      ]);
      $field->save();

      $form_dislay_storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
      $form_display = $form_dislay_storage->load('registration.' . $this->entity->id() . '.default');

      if (!$form_display) {
        $form_display = $form_dislay_storage->create([
          'targetEntityType' => 'registration',
          'bundle' => $this->entity->id(),
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }

      $form_display->setComponent('field_participants', [
        'type' => 'inline_entity_form_complex',
        'weight' => 10,
        'settings' => [
          'form_mode' => 'inline',
          'label_singular' => '',
          'label_plural' => '',
          'allow_new' => true,
          'match_operator' => 'CONTAINS',
          'override_labels' => false,
          'collapsible' => false,
          'collapsed' => false,
          'allow_existing' => false,
          'allow_duplicate' => false,
        ],
      ]);
      $form_display->removeComponent('user_id');
      $form_display->removeComponent('event_id');
      $form_display->save();
    }

    return $field;
  }

}
