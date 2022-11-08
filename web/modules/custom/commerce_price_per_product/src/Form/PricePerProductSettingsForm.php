<?php

namespace Drupal\commerce_price_per_product\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class PricePerProductSettingsForm.
 */
class PricePerProductSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'price_per_product_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $product_types = \Drupal::service('entity_type.bundle.info')->getBundleInfo('commerce_product');
    $product_types_array = [];

    foreach ($product_types as $type => $label) {
      $product_types_array[$type] = $label['label'];
    }

    $form['product_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Product Types'),
      '#options' => $product_types_array,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['product_types'] as $key => $value) {
      if ($value) {
        $field_storage = FieldStorageConfig::loadByName('commerce_product', 'field_price_per_product');
        $field = FieldConfig::loadByName('commerce_product', 'oblecenie', 'field_price_per_product');
        if (empty($field)) {

        }
      }
    }
  }

}
