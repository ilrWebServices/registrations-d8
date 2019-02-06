<?php

namespace Drupal\erf\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EntityRegistrationForm.
 */
class EntityRegistrationForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityRegistrationForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('event')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $source_entity = NULL, $registration_type = 'default') {
    $form = [];

    // Create a new, empty registration.
    $registration = $this->entityTypeManager->getStorage('registration')->create([
      'type' => $registration_type
    ]);

    $form_state->set('registration', $registration);

    // Load an Add Registration form for the registration type associated
    // with this product.
    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('registration.' . $registration_type . '.default');
    $form_state->set('form_display', $form_display);
    $form['#parents'] = [];

    // Add the new registration entity form widgets to this form.
    foreach ($form_display->getComponents() as $name => $component) {
      $widget = $form_display->getRenderer($name);
      if (!$widget) {
        continue;
      }

      $items = $registration->get($name);
      $items->filterEmptyItems();
      $form[$name] = $widget->form($items, $form, $form_state);
      $form[$name]['#access'] = $items->access('edit');
      $form[$name]['#weight'] = $component['weight'];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_display = $form_state->get('form_display');
    $registration = $form_state->get('registration');
    $extracted = $form_display->extractFormValues($registration, $form, $form_state);

    if ($registration->save()) {
      drupal_set_message($this->t('Registration saved.'));
    }
  }

}
