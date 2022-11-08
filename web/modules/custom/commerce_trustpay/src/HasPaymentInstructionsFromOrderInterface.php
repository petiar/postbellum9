<?php

namespace Drupal\commerce_trustpay;

use Drupal\commerce_order\Entity\Order;

/**
 * Defines the interface for gateways which show payment instructions based on the
 * order entity. I created this interface as a replacement of the HasPaymentInstructionsInterface
 * because I need to provide payment information for orders without any payment.
 *
 * Payment instructions are usually shown on checkout complete.
 */
interface HasPaymentInstructionsFromOrderInterface {

  /**
   * Builds the payment instructions from the Order
   *
   * @param Order $order
   *   The payment.
   *
   * @return array
   *   A render array containing the payment instructions.
   */
  public function buildPaymentInstructions(Order $order): array;

}
