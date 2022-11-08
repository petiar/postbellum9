<?php


namespace Drupal\postbellum_helpers\Plugin\Commerce\ShippingMethod;


use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Form\FormStateInterface;
use Drupal\physical\Weight;
use Drupal\state_machine\WorkflowManager;
use Drupal\state_machine\WorkflowManagerInterface;

/**
 * Provides the WeightQuote shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "weight_quote",
 *   label = @Translation("Weight quote"),
 * )
 */
class WeightQuote extends ShippingMethodBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, WorkflowManagerInterface $workflow_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager, $workflow_manager);
    $this->services['weight_quote'] = new ShippingService('weight_quote', $this->configuration['rate_label']);
    $config = \Drupal::service('config.factory')->getEditable('postbellum.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'rate_label' => '',
        'rate_description' => '',
        'rate_amount' => NULL,
        'services' => ['default'],
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Rate label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['rate_label'] = $values['rate_label'];
    }
  }

  public function calculateRates(ShipmentInterface $shipment) {
    $rates = [];
    $order_weight = $shipment->getWeight();

    // Get the weight quotes
    $config = \Drupal::service('config.factory')->getEditable('postbellum.settings');

    $quotes = $config->get('shipping_quotes');
    foreach ($quotes as $quote) {
      $weight_from = new Weight($quote['weight_from']['number'], $quote['weight_from']['unit']);
      $weight_to = new Weight($quote['weight_to']['number'], $quote['weight_to']['unit']);
      if ($order_weight->greaterThanOrEqual($weight_from) && $order_weight->lessThan($weight_to)) {
        $amount = new Price($quote['price']['number'], $quote['price']['currency_code']);
        $trans = \Drupal::transliteration();
        $rate_id = 'weight_quote_' . strtolower($trans->transliterate($quote['title']));
        $rates[] = new ShippingRate([
          'shipping_method_id' => $this->parentEntity->id(),
          'service' => $this->services['weight_quote'],
          'amount' => $amount,
          'description' => 'Description',
        ]);
        \Drupal::logger('petiar')->debug(print_r($rates, true));
      }
    }
    if (!$rates) {
      \Drupal::logger('postbellum_weight_quote')->warning('No shipping rate found for this order. Order weight: %weight',
        [
          '%weight' => $order_weight->getNumber(),
        ]
      );
    }
    return $rates;
  }
}
