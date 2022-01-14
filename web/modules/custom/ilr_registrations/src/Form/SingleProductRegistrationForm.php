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

    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Email address',
      '#required' => TRUE,
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $commerce_product_variation = $form_state->get('commerce_product_variation');
    $product = $commerce_product_variation->product_id->entity;

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

    $route_provider = \Drupal::service('router.route_provider');
    $cart_route = $route_provider->getRouteByName('commerce_cart.page');
    $form_state->setRedirect($cart_route);

    // dump($registration); die();
  }

}
