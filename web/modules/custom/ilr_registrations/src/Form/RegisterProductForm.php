<?php

namespace Drupal\ilr_registrations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\ilr_registrations\Plugin\Field\FieldFormatter\RegistrationFormFormatter;

/**
 * Class RegisterProductForm.
 */
class RegisterProductForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The current store provider.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * Constructs a new RegisterProductForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CartManagerInterface $cart_manager,
    CartProviderInterface $cart_provider,
    CurrentStoreInterface $current_store
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->currentStore = $current_store;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_store.current_store')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProductInterface $product = NULL, $registration_type = 'default', RegistrationFormFormatter $registration_form_formatter = NULL) {
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

    // Get the product variations (classes) for this registration. Add these
    // variations as options to this form. If no product exists, skip this part.
    if ($product) {
      // @see \Drupal\ilr_registrations\Element\CommerceProductVariationsElement
      $form['variation'] = [
        '#type' => 'commerce_product_variations_entity_selector',
        '#product' => $product,
        '#title' => 'Available Classes',
        '#view_mode' => $registration_form_formatter->getSetting('variation_view_mode'),
        '#required' => TRUE,
      ];
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_display = $form_state->get('form_display');
    $registration = $form_state->get('registration');
    $extracted = $form_display->extractFormValues($registration, $form, $form_state);

    // Add the selected class product variation, if one exists, to a new or
    // existing cart.
    if ($variation_id = $form_state->getValue('variation')) {
      // Get or create a cart for the current user.
      $cart = $this->cartProvider->getCart('default', $this->currentStore->getStore(), $this->currentUser());
      if (empty($cart)) {
        $cart = $this->cartProvider->createCart('default', $this->currentStore->getStore(), $this->currentUser());
      }

      // Get a count of the participants being registered, which will determine
      // the count of items in the cart.
      if ($form_state->hasValue('participants')) {
        $participants = $form_state->getValue('participants');
        $quantity = isset($participants['entities']) ? count($participants['entities']) : 1;
      }
      else {
        $quantity = 1;
      }

      // Load the selected variation and add it to the cart. The cart manager
      // will create a user notification.
      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);
      $order_item = $this->cartManager->addEntity($cart, $variation, $quantity);

      // Link the order item as a reference on the newly created registration.
      if ($order_item) {
        $registration->event_id->entity = $order_item;

        if ($registration->save()) {
          drupal_set_message($this->t('Registration saved.'));
        }
      }
    }
  }

}
