<?php

namespace Drupal\ilr_registrations\Form;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SingleProductRegistrationForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SingleProductRegistrationForm form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'single_product_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProductVariationInterface $commerce_product_variation = NULL) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $commerce_product_variation->product_id->entity;

    if (!$product->hasField('registration_type')) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $product->getFieldDefinition('registration_type');

    if ($field_definition->getType() !== 'entity_reference') {
      throw new NotFoundHttpException();
    }

    $form_state->set('commerce_product_variation', $commerce_product_variation);

    $form['registering_for'] = [
      '#type' => 'radios',
      '#options' => [
        'single' => 'Registering myself',
        'multiple' => 'Registering multiple people'
      ],
      '#default_value' => 'single',
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Email address',
      '#states' => [
        'visible' => [
          ':input[name="registering_for"]' => ['value' => 'single'],
        ],
        'required' => [
          [':input[name="registering_for"]' => ['value' => 'single']],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['registering_for'] === 'single' && empty($values['email'])) {
      $form_state->setErrorByName('email', $this->t('Please include your email address'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_provider = \Drupal::service('router.route_provider');
    $commerce_product_variation = $form_state->get('commerce_product_variation');
    $product = $commerce_product_variation->product_id->entity;

    if ($form_state->getValue('registering_for') === 'multiple') {
      $form_state->setRedirect('entity.commerce_product.canonical', [
        'commerce_product' => $product->id(),
      ],
      ['query' => ['v' => $commerce_product_variation->id()]]);
    }
    else {
      $participant = $this->entityTypeManager->getStorage('participant')->create([
        'type' => 'basic',
        'mail' => $form_state->getValue('email'),
      ]);

      $registration = $this->entityTypeManager->getStorage('registration')->create([
        'type' => $product->registration_type->target_id,
        'entity_type' => $product->getEntityTypeId(),
        'entity_id' => $product->id(),
        'participants' => [$participant],
        'product_variation' => [$commerce_product_variation],
      ]);

      // @todo Check for existing registration in cart and prevent dupes.
      $registration->save();

      $form_state->setRedirect('commerce_cart.page');
    }
  }

}
