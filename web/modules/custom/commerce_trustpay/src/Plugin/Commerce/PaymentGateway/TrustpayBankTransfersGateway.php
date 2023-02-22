<?php

namespace Drupal\commerce_trustpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Annotation\CommercePaymentGateway;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_trustpay\TrustpayRequestGenerator;
use Drupal\Console\Bootstrap\Drupal;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Trustpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "trustpay_bank_transfers_gateway",
 *   label = "Trustpay Bank Transfers Gateway",
 *   display_label = "Trustpay Instant Bank Transfers Gateway",
 *    forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_trustpay\PluginForm\CommerceTrustpayBankTransfersPaymentForm",
 *   },
 *   requires_billing_information = FALSE,
 *   modes = {}
 * )
 */
class TrustpayBankTransfersGateway extends TrustpayGateway {
  public function buildPaymentInstructionsDeleted (Order $order): array {
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

    $signature_array = [
      'account_id' => $params['AccountId'],
      'amount' => $params['Amount'],
      'currency' => $params['Currency'],
      'type' => $params['Type'],
      'result_code' => $params['ResultCode'],
      'counter_account' => $params['CounterAccount'] ?? NULL,
      'counter_account_name' => $params['CounterAccountName'] ?? NULL,
      'trustpay_order_id' => $params['OrderId'] ?? NULL, // this is NOT the drupal commerce order id
      'payment_request_id' => $params['PaymentId'],
      'reference' => $params['Reference'],
      'refuse_reason' => $params['RefuseReason'] ?? NULL,
    ];

    $this->validatePayment($signature_array, $params['Signature']);
  }

  public function getGatewayUrl() {
    return 'https://amapi.trustpay.eu/mapi5/wire/paypopup';
  }
}
