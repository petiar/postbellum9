<?php

namespace Drupal\commerce_trustpay;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\Core\Url;

class TrustpayRequestGenerator {
  private $order;
  private $returnUrl;
  private $cancelUrl;
  private $exceptionUrl;

  public const TRUSTCARD_PAYMENT_REQUEST_URL = 'https://amapi.trustpay.eu/mapi5/Card/PayPopup';
  public const TRUSTPAY_PAYMENT_REQUEST_URL = 'https://amapi.trustpay.eu/mapi5/wire/paypopup';

  public function __construct(Order $order) {
    $this->order = $order;
    $this->returnUrl = Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    $this->cancelUrl = Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $this->order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }

  public function setReturnUrl($url) {
    $this->returnUrl = $url;
  }

  public function setCancelUrl($url) {
    $this->cancelUrl = $url;
  }

  public function setExceptionUrl($url) {
    $this->exceptionUrl = $url;
  }

  public function generateRequestUrl($baseUrl) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment_gateway')->loadByProperties([
        'plugin' => 'trustpay_gateway',
      ]);
    $payment_gateway_plugin = reset($payment_gateway_plugin)->getPlugin();
    $accountId = $payment_gateway_plugin->getConfiguration()['aid'];
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
    $returnUrl = $this->returnUrl ?: '';
    $cancelUrl = $this->cancelUrl ?: '';
    $errorUrl =   Url::fromRoute('commerce_trustpay.error', [], ['absolute' => TRUE])->toString();
    $notifyUrl = $payment_gateway_plugin->getNotifyUrl()->toString();
    $description = urlencode($payment_gateway_plugin->getConfiguration()['description']);

    if ($baseUrl == $this::TRUSTPAY_PAYMENT_REQUEST_URL) {
      $sigData = sprintf("%d/%s/%s/%s/%d", $accountId, number_format($amount, 2, '.', ''), $currency, $reference, $paymentType);
      $signature = commerce_trustpay_get_signature($secretKey, $sigData);
      $url = sprintf(
        "%s?AccountId=%d&Amount=%s&Currency=%s&Reference=%s&PaymentType=%d&Signature=%s&returnUrl=%s&cancelUrl=%s&errorUrl=%s&notifyUrl=%s&Description=%s",
        $baseUrl, $accountId, number_format($amount, 2, '.', ''), $currency, urlencode($reference), $paymentType, $signature, $returnUrl, $cancelUrl, $errorUrl, $notifyUrl, $description);
    }
    else {
      $sigData = sprintf("%d/%s/%s/%s/%d/%s/%s/%s/%s/%s/%s", $accountId, number_format($amount, 2, '.', ''), $currency, $reference, $paymentType, $billingcity, $billingcountry, $billingpostcode, $billingstreet, $cardholder, $email);
      $signature = commerce_trustpay_get_signature($secretKey, $sigData);
      $url = sprintf(
        "%s?AccountId=%d&Amount=%s&Currency=%s&Reference=%s&PaymentType=%d&Signature=%s&BillingCity=%s&BillingCountry=%s&BillingPostcode=%s&BillingStreet=%s&CardHolder=%s&Email=%s&returnUrl=%s&cancelUrl=%s&errorUrl=%s&notifyUrl=%s&Description=%s",
        $baseUrl, $accountId, number_format($amount, 2, '.', ''), $currency, urlencode($reference), $paymentType, $signature, $billingcity, $billingcountry, $billingpostcode, $billingstreet, $cardholder, $email, $returnUrl, $cancelUrl, $errorUrl, $notifyUrl, $description);
    }
    return $url;
  }
}
