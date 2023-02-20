<?php

namespace Drupal\commerce_trustpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Annotation\CommercePaymentGateway;
use Drupal\commerce_price\Price;
use Drupal\commerce_trustpay\TrustpayRequestGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Trustpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "trustpay_card_gateway",
 *   label = "Trustpay Card Gateway",
 *   display_label = "Trustpay Card payments Gateway",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_trustpay\PluginForm\CommerceTrustpayCardPaymentForm",
 *   },
 *   requires_billing_information = FALSE,
 *   modes = {}
 * )
 */
class TrustpayCardGateway extends TrustpayGateway {

  public function buildPaymentInstructionDeleted (Order $order): array {
    $trustpayRequest = new TrustpayRequestGenerator($order);
    return [
      '#type' => 'inline_template',
      '#template' => '<div id="trustpay-payment-instructions"><h3>{{ header_text }}</h3><p>{{ intro_text }}</p><p><a href="{{ url }}">Pay now</a></p></div>',
      '#context' => [
        'api_key' => $this->getAid(),
        'purchase_id' => $order->id(),
        'mode' => $this->getMode(),
        'url' => $trustpayRequest->generateRequestUrl($this->parentEntity),
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
    \Drupal::logger('commerce_trustpay')->debug('Notify request: %params', ['%params' => json_encode($params)]);

    $signatureArray = [
      'account_id' => $params['AccountId'],
      'amount' => $params['Amount'],
      'currency' => $params['Currency'],
      'reference' => $params['Reference'],
      'type' => $params['Type'],
      'result_code' => $params['ResultCode'],
      'payment_request_id' => $params['PaymentRequestId'],
      'card_id' => $params['CardId'] ?? NULL,
      'card_mask' => $params['CardMask'] ?? NULL,
      'card_expiration' => $params['CardExpiration'] ?? NULL,
      'auth_number' => $params['AuthNumber'] ?? NULL,
    ];

    $this->validatePayment($signatureArray, $params['Signature']);
  }

  public function getGatewayUrl() {
    return 'https://amapi.trustpay.eu/mapi5/Card/PayPopup';
  }

}
