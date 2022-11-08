<?php

namespace Drupal\commerce_trustpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Annotation\CommercePaymentGateway;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_trustpay\HasPaymentInstructionsFromOrderInterface;
use Drupal\commerce_trustpay\TrustpayRequestGenerator;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Trustpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "trustpay_gateway",
 *   label = "Trustpay Gateway",
 *   display_label = "Trustpay Payment Gateway",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_trustpay\PluginForm\CommerceTrustpayPaymentForm",
 *   },
 *   requires_billing_information = FALSE,
 *   modes = {}
 * )
 */
class TrustpayGateway extends OffsitePaymentGatewayBase implements HasPaymentInstructionsFromOrderInterface, SupportsNotificationsInterface {

  private function getAid() {
    return $this->configuration['aid'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'aid' => '',
        'secret' => '',
        'order_valid_time' => 168,
        'interest_free_days_merchant' => 0,
        'description' => '',
      ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
    }
  }

  public function buildPaymentInstructions (Order $order): array {
    $trustpayRequest = new TrustpayRequestGenerator($order);
    return [
      '#type' => 'inline_template',
      '#template' => '<div id="trustpay-payment-instructions"><h3>{{ header_text }}</h3><p>{{ intro_text }}</p><p><a href="{{ url }}">Pay now</a></p></div>',
      '#context' => [
        'api_key' => $this->getAid(),
        'purchase_id' => $order->id(),
        'mode' => $this->getMode(),
        'url' => $trustpayRequest->generateRequestUrl(TrustpayRequestGenerator::TRUSTPAY_PAYMENT_REQUEST_URL),
        'header_text' => $this->t('Pay for the order to complete the purchase'),
        'intro_text' => $this->t('Complete your order by paying for your purchase with Trustpay. By clicking on "Pay Now" a window opens and you can complete your purchase with Trustpay.'),
        'thankyou_header' => $this->t('Thank you for your purchase!'),
        'thankyou_text' => $this->t('You have successfully completed the order and paid for your purchase with Trustpay.'),
      ],
    ];
  }

  public function onNotify(Request $request) {
    // Get query parameters from request.
    $params = $request->query->all();

    // Get Order ID from request and load Order.
    $order_id = $params['Reference'];
    $order = Order::load($order_id);

    $accountId = $params['AccountId'];
    $amount = $params['Amount'];
    $currency = $params['Currency'];
    $reference = $params['Reference'];
    $type = $params['Type'];
    $resultCode = $params['ResultCode'];
    $paymentRequestId = $params['PaymentRequestId'];
    $cardId = $params['CardId'];
    $cardMask = $params['CardMask'];
    $cardExpiration = $params['CardExpiration'];
    $authNumber = $params['AuthNumber'];

    $signatureMessage = sprintf("%d/%s/%s/%s/%d/%s/%s/%s/%s/%s/%s",
      $accountId,
      $amount,
      $currency,
      $reference,
      $type,
      $resultCode,
      $paymentRequestId,
      $cardId,
      $cardMask,
      $cardExpiration,
      $authNumber);

    $key = $this->getConfiguration()['secret_key'];
    $signatureFromData = commerce_trustpay_get_signature($key, $signatureMessage);

    \Drupal::logger('commerce_trustpay')->debug('Notify request: %params', ['%params' => json_encode($params)]);
    if (isset($params['Signature']) && $params['Signature'] == $signatureFromData) {
      \Drupal::logger('commerce_trustpay')->notice('Payment notification SIG check OK!', []);

      if ($params['ResultCode'] == 0) {
        $state = 'completed';
      }
      else {
        $state = 'pending';
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
      $payment_gateway = \Drupal::entityTypeManager()
        ->getStorage('commerce_payment_gateway')->loadByProperties([
          'plugin' => 'trustpay_gateway',
        ]);
      $current_user = $order->getCustomerId();

      $payment = Payment::create([
        'state' => $state,
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => reset($payment_gateway)->get('id'),
        'order_id' => $order->id(),
        'remote_id' => $paymentRequestId,
        'payment_gateway_mode' => 0,
        'expires' => 0,
        'uid' => $current_user,
      ]);
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_trustpay')->warning('Payment notification not from Trustpay!', []);
    }
  }

}
