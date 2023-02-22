<?php
namespace Drupal\commerce_trustpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_price\MinorUnitsConverterInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_trustpay\HasPaymentInstructionsFromOrderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class TrustpayGateway extends OffsitePaymentGatewayBase implements SupportsNotificationsInterface, TrustpayGatewayInterface {
  const TRUSTPAY_RESPONSE_SUCCESS = 0;
  const TRUSTPAY_RESPONSE_PENDING = 1;
  const TRUSTPAY_RESPONSE_ANNOUNCED = 2;
  const TRUSTPAY_RESPONSE_AUTHORIZED = 3;
  const TRUSTPAY_RESPONSE_PROCESSING = 4;
  const TRUSTPAY_RESPONSE_AUTHORIZED_ONLY = 5;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'aid' => '',
        'secret' => '',
        'description' => '',
        'notify_catcher_url',
      ] + parent::defaultConfiguration();
  }
  public function buildPaymentInstructions(Order $order): array {
    return [];
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['aid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trustpay Account ID'),
      '#default_value' => $this->configuration['aid'],
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#default_value' => $this->configuration['secret'],
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'] ?: '',
      '#required' => FALSE,
      '#description' => t('Payment description on payment gateway page.'),
    ];

    $form['notify_catcher_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notify URL catcher'),
      '#default_value' => $this->configuration['notify_catcher_url'],
      '#required' => FALSE,
      '#description' => $this->t('URL for catching notify response for debuging purposes.'),
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

      $this->configuration['aid'] = $values['aid'];
      $this->configuration['secret'] = $values['secret'];
      $this->configuration['description'] = $values['description'];
      $this->configuration['notify_catcher_url'] = $values['notify_catcher_url'];
    }
  }

  protected function getAid() {
    return $this->configuration['aid'];
  }

  protected function validatePayment($signature_array, $signature_from_request) {
    $order = Order::load($signature_array['reference']);
    $signatureMessage = implode("/", array_filter($signature_array, function ($item) {
      return strval($item) !== '';
    }));

    $key = $this->getConfiguration()['secret'];
    $signature_from_data = commerce_trustpay_get_signature($key, $signatureMessage);

    if ($signature_from_request == $signature_from_data) {
      if (in_array($signature_array['ResultCode'], [
        self::TRUSTPAY_RESPONSE_SUCCESS,
        self::TRUSTPAY_RESPONSE_AUTHORIZED,
        self::TRUSTPAY_RESPONSE_PROCESSING
      ])) {
        $state = 'completed';
      }
      else {
        $state = 'pending';
      }

      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $price_paid = new Price($signature_array['amount'], $signature_array['currency']);
      $payment = $payment_storage->create([
        'state' => $state,
        'amount' => $price_paid,
        'payment_gateway' => $this->parentEntity->id(),
        'order_id' => $order->id(),
        'remote_id' => $signature_array['payment_request_id'],
        'remote_state' => $signature_array['result_code'],
      ]);
      $payment->save();
      \Drupal::logger('commerce_trustpay')->info('Payment receiver for the order ' . $order->getOrderNumber());
    }
    else {
      \Drupal::logger('commerce_trustpay')->notice('Payment notification not from Trustpay!', []);
    }

  }
}
