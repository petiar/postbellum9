<?php

namespace Drupal\commerce_trustpay\Controller;

use Drupal\Core\Controller\ControllerBase;

class TrustpayResponse extends ControllerBase {
  public function onError() {
    $params = \Drupal::request()->query->all();
    \Drupal::logger('commerce_trustpay')->critical('Error response from Trustpay payment gateway. Reference: %reference. ResultCode: %code. PaymentRequestId: %id', [
      '%reference' => $params['Reference'],
      '%code' => $params['ResultCode'],
      '%id' => $params['PaymentRequestId'],
    ]);

    $message = $this->getResultMessage($params['ResultCode']);

    return [
      '#title' => $message['title'],
      '#theme' => 'commerce_trustpay_error',
      '#description' => $message['description'],
    ];
  }

  public function getResultMessage($code) {
    $return_codes = [
      1001 => ['Invalid request', 'Some of the parameters in request are missing or have invalid format.'],
      1002 => ['Unknown account', 'Account with specified ID was not found. Please check that you are using correct AID for live environment.'],
      1003 => ['Merchant account disabled', 'Merchant account has been disabled.'],
      1004 => ['Invalid signature', 'Verification of request signature has failed.'],
      1005 => ['User cancel', 'Customer has cancelled the payment.'],
      1006 => ['Invalid authentication', 'Request was not properly authenticated.'],
      1007 => ['Insufficient balance', 'Requested transaction amount is greater than disposable balance.'],
      1008 => ['Service not allowed', 'Service cannot be used or permission to use given service has not been granted. If you receive this code, please contact TrustPay for more information.'],
      1009 => ['Processing ID used', 'Specified processing ID was already used. Please generate a new PID using Setup payment service call.'],
      1010 => ['Transaction not found', 'Transaction with specified ID was not found.'],
      1011 => ['Unsupported transaction', 'The requested action is not supported for the transaction.'],
      1014 => ['Rejected transaction', 'Transaction was rejected by issuer or gateway.'],
      1015 => ['Declined by risk', 'Transaction was rejected by risk department.'],
      1100 => ['General Error', 'Internal error has occurred. Please contact TrustPay to resolve this issue.'],
      1101 => ['Unsupported currency conversion', 'Currency conversion for requested currencies is not supported.'],
    ];
    $message = [
      'title' => 'Uknown error',
      'description' => 'Something very bad has just happended',
    ];
    if (array_key_exists($code, $return_codes)) {
      $message = [
        'title' => $return_codes[$code][0],
        'description' => $return_codes[$code][1],
      ];
    }
    return $message;
  }
}
