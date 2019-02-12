<?php

namespace Drupal\erf\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class RegistrationTypeForm.
 */
class RegistrationTypeForm extends EntityForm {

  /**
   * The participant type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $participantTypeStorage;

  /**
   * The field storage config storage.
   *
   * @var \Drupal\field\FieldStorageConfigStorage
   */
  protected $fieldStorageConfigStorage;

  /**
   * The field config storage.
   *
   * @var \Drupal\field\FieldConfigStorage
   */
  protected $fieldConfigStorage;

  /**
   * The entity form display storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $entityFormDisplayStorage;

  /**
   * The registration handler plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $registrationHandlerManager;

  /**
   * Creates a new RegistrationTypeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PluginManagerInterface $registration_handler_manager) {
    $this->participantTypeStorage = $entity_type_manager->getStorage('participant_type');
    $this->fieldStorageConfigStorage = $entity_type_manager->getStorage('field_storage_config');
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
    $this->entityFormDisplayStorage = $entity_type_manager->getStorage('entity_form_display');
    $this->registrationHandlerManager = $registration_handler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.registration_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $registration_type = $this->entity;
    $participant_types = $this->participantTypeStorage->loadMultiple();
    $participant_type_options = [];
    $registration_handler_options = [];
    $registration_handler_defaults = [];

    foreach ($this->registrationHandlerManager->getDefinitions() as $definition) {
      $registration_handler_options[$definition['id']] = $definition['label'];
    }

    foreach ($participant_types as $participant_type) {
      $participant_type_options[$participant_type->id()] = $participant_type->label();
    }

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
        'exists' => '\Drupal\erf\Entity\RegistrationType::load',
      ],
      '#disabled' => !$registration_type->isNew(),
    ];

    $form['handlers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Handlers'),
      '#options' => $registration_handler_options,
      '#default_value' => $registration_type->getHandlers(),
    ];

    if ($registration_type->isNew()) {
      $form['participant_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Participant type'),
        '#options' => $participant_type_options,
        '#default_value' => 'default',
        '#description' => $this->t('Select a default participant type for this registration type. You can change this later by editing the Participants field.'),
      ];
    }

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

        if ($form_state->hasValue('participant_type')) {
          $this->addParticipantsField($form_state->getValue('participant_type'));
        }
        else {
          $this->addParticipantsField();
        }
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
   *
   * @see node_add_body_field() and commerce_product_add_variations_field().
   */
  private function addParticipantsField(string $participant_type = 'default') {
    $field_storage = $this->fieldStorageConfigStorage->load('registration.participants');
    $field = $this->fieldConfigStorage->load('registration.' . $this->entity->id() . '.participants');

    if (empty($field)) {
      $field = $this->fieldConfigStorage->create([
        'field_storage' => $field_storage,
        'bundle' => $this->entity->id(),
        'label' => 'Participants',
        'settings' => [
          'handler' => 'default:participant',
          'handler_settings' => [
            'target_bundles' => [
              'default' => $participant_type,
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

      $form_display = $this->entityFormDisplayStorage->load('registration.' . $this->entity->id() . '.default');

      if (!$form_display) {
        $form_display = $this->entityFormDisplayStorage->create([
          'targetEntityType' => 'registration',
          'bundle' => $this->entity->id(),
          'mode' => 'default',
          'status' => TRUE,
        ]);
      }

      $form_display->setComponent('participants', [
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
