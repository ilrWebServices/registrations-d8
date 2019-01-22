<?php

namespace Drupal\ilr_registrations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ParticipantTypeForm.
 */
class ParticipantTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $participant_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $participant_type->label(),
      '#description' => $this->t("Label for the Participant type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $participant_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ilr_registrations\Entity\ParticipantType::load',
      ],
      '#disabled' => !$participant_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $participant_type = $this->entity;
    $status = $participant_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Participant type.', [
          '%label' => $participant_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Participant type.', [
          '%label' => $participant_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($participant_type->toUrl('collection'));
  }

}
