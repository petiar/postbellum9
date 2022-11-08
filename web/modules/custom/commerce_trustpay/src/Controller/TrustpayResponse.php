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

    return [
      '#type' => 'markup',
      '#markup' => $this->t('There was a problem with payment via Trustpay. Sorry for the inconvenience.')
    ];
  }
}
