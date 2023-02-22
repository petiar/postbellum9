<?php

namespace Drupal\commerce_trustpay;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\Core\Url;

class TrustpayRequestGenerator {
  private $order;
  private $return_url;
  private $cancel_url;
  private $exception_url;

  public function __construct(Order $order) {
    $this->order = $order;
    $this->return_url = Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    $this->cancel_url = Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  /**
   * @param \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway
   *
   * @return string
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function generateRequestUrl(PaymentGateway $payment_gateway): string {
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    $base_url = $payment_gateway_plugin->getGatewayUrl();
    $account_id = $payment_gateway_plugin->getConfiguration()['aid'];
    $amount = number_format($this->order->getTotalPrice()->getNumber(), 2, '.', '');
    $billingProfile = $this->order->getBillingProfile()->get('address')->first()->getValue();
    $billingcity = $billingProfile['locality'];
    $billingcountry = $billingProfile['country_code'];
    $billingpostcode = $billingProfile['postal_code'];
    $billingstreet = $billingProfile['address_line1'];
    $cardholder = $billingProfile['given_name'] . ' ' . $billingProfile['family_name'];
    $currency = $this->order->getBalance()->getCurrencyCode();
    $email = $this->order->getEmail();
    $reference = $this->order->id();
    $paymentType = 0;
    $secretKey = $payment_gateway_plugin->getConfiguration()['secret'];

    // Return, Cancel and Error urls.
    $returnUrl = urlencode($this->return_url ?: '');
    $cancelUrl = urlencode($this->cancel_url ?: '');
    $errorUrl = urlencode(Url::fromRoute('commerce_trustpay.error', [], ['absolute' => TRUE])->toString());
    if ($payment_gateway_plugin->getConfiguration()['notify_catcher_url']) {
      $notifyUrl = urlencode($payment_gateway_plugin->getConfiguration()['notify_catcher_url']);
    }
    else {
      $notifyUrl = urlencode($payment_gateway_plugin->getNotifyUrl()->toString());
    }
    $description = urlencode($payment_gateway_plugin->getConfiguration()['description']);

    switch ($payment_gateway->getPluginId()) {
      case 'trustpay_card_gateway':
        $sigData = sprintf("%d/%s/%s/%s/%d/%s/%s/%s/%s/%s/%s", $account_id, number_format($amount, 2, '.', ''), $currency, $reference, $paymentType, $billingcity, $billingcountry, $billingpostcode, $billingstreet, $cardholder, $email);
        $signature = commerce_trustpay_get_signature($secretKey, $sigData);
        $url = sprintf(
          "%s?AccountId=%d&Amount=%s&Currency=%s&Reference=%s&PaymentType=%d&Signature=%s&BillingCity=%s&BillingCountry=%s&BillingPostcode=%s&BillingStreet=%s&CardHolder=%s&Email=%s&returnUrl=%s&cancelUrl=%s&errorUrl=%s&NotificationUrl=%s&Description=%s",
          $base_url, $account_id, number_format($amount, 2, '.', ''), $currency, urlencode($reference), $paymentType, $signature, $billingcity, $billingcountry, $billingpostcode, $billingstreet, $cardholder, $email, $returnUrl, $cancelUrl, $errorUrl, $notifyUrl, $description);
        break;
      case 'trustpay_bank_transfers_gateway':
        $sigData = sprintf("%d/%s/%s/%s/%d", $account_id, number_format($amount, 2, '.', ''), $currency, $reference, $paymentType);
        $signature = commerce_trustpay_get_signature($secretKey, $sigData);
        $url = sprintf(
          "%s?AccountId=%d&Amount=%s&Currency=%s&Reference=%s&PaymentType=%d&Signature=%s&returnUrl=%s&cancelUrl=%s&errorUrl=%s&NotificationUrl=%s&Description=%s",
          $base_url, $account_id, number_format($amount, 2, '.', ''), $currency, urlencode($reference), $paymentType, $signature, $returnUrl, $cancelUrl, $errorUrl, $notifyUrl, $description);
    }
    return $url;
  }
}
