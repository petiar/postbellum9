<?php

namespace Drupal\postbellum_helpers\Plugin\Block;

use Drupal;
use \Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class CartBlock
 *
 * @package Drupal\postbellum_cart_block\Plugin\Block
 *
 * @Block(
 *   id = "postbellum_cart_block",
 *   admin_label = @Translation("PB Cart Block"),
 * )
 *
 */

class PostbellumCartBlock extends BlockBase {
  public function build() {
    return [
      '#theme' => 'postbellum_cart',
      '#cart_quantity' => $this->getCartCount(),
    ];
  }

  public function getCacheMaxAge() {
    return 0;
  }

  private function getCartCount() {
    $cart_quantity = null;
    // get the store first
    $store = \Drupal::service('commerce_store.current_store')->getStore();
    // then get the cart
    if (Drupal::service('commerce_cart.cart_provider')) {
      $cart = \Drupal::service('commerce_cart.cart_provider')
        ->getCart('default');
      // get cart items
      if ($cart) {
        $items = ($cart->getItems());
        $quantity = 0;
        // loop through the cart items
        foreach ($items as $item) {
          $quantity += ($item->getQuantity());
        }
        $cart_quantity = $quantity;
      }
    }
    return $cart_quantity;
  }
}
