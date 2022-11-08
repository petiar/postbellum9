<?php

namespace Drupal\postbellum_helpers\Plugin\Block;

use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'ProductBlock' block.
 *
 * @Block(
 *  id = "product_block",
 *  admin_label = @Translation("Product block"),
 * )
 */
class ProductBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['product'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Product'),
      '#description' => $this->t('Vyberte produkt, ktorý sa má zobraziť v tomto bloku.'),
      '#default_value' => Product::load($this->configuration['product']),
      '#weight' => '0',
      '#target_type' => 'commerce_product'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['product'] = $form_state->getValue('product');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($this->configuration['product']) {
      $product = Product::load($this->configuration['product']);
      $build = [];
      if ($product) {
        $build['#theme'] = 'product_block';
        $view_builder = \Drupal::entityTypeManager()
          ->getViewBuilder('commerce_product');
        $pre_render = $view_builder->view($product, 'views');
        $build['#product'] = render($pre_render);
      }
    }
    return $build;
  }
}
