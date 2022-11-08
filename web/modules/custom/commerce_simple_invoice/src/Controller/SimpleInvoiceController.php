<?php

namespace Drupal\commerce_simple_invoice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\profile\ProfileViewBuilder;
use Dompdf\Dompdf;

/**
 * Class SimpleInvoiceController.
 */
class SimpleInvoiceController extends ControllerBase {

  /**
   * Hello.
   *
   * @return array
   *   Return Hello string.
   */
  public function generatePdf($commerce_order) {
    $order = Order::load($commerce_order);

    $billing_information = $order->getBillingProfile();
    $totals = $order->getTotalPrice();
    $shipping_information = \Drupal::service('commerce_shipping.order_shipment_summary')->build($order);

    $element = [
      '#theme' => 'commerce_simple_invoice',
      '#order_entity' => $order,
      '#billing_information' => render(\Drupal::entityTypeManager()->getViewBuilder('profile')->view($billing_information)),
      '#shipping_information' => $shipping_information,
      '#totals' => $totals,
    ];

    $output = new \Symfony\Component\HttpFoundation\Response(render($element));

    $pdf = new Dompdf();
    $pdf->loadHtml(mb_convert_encoding($output->getContent(), 'HTML-ENTITIES', 'UTF-8'));
    $pdf->setPaper('A4', 'portrait');
    $pdf->render();
    $pdf->stream('invoice-' . $commerce_order . '.pdf');
  }
}
