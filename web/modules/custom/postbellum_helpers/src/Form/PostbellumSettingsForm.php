<?php

namespace Drupal\postbellum_helpers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PostbellumSettingsForm extends ConfigFormBase {
  public function getFormId() {
    return 'postbellum_settings_form';
  }

  public function getEditableConfigNames() {
    return [
      'postbellum.settings'
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('postbellum.settings');
    $quotes = $config->get('shipping_quotes');

    $condition = $form_state->get('conditions_count');
    $form['#tree'] = TRUE;

    if (empty($condition)) {
      $form_state->set('conditions_count', count($quotes));
    }

    if ($form_state->get('conditions_count') > 0) {
      $value = $form_state->get('conditions_count');
    }
    else {
      $value = 1;
    }

    $form['condition'] = array(
      '#type' => 'table',
      '#caption' => $this
        ->t('Weight shipping quotes'),
      '#header' => array(
        $this
          ->t('Title'),
        $this
          ->t('Weight from'),
        $this
          ->t('Weight to'),
        $this
          ->t('Price'),
      ),
    );

    for ($i = 0; $i < $value; $i++) {

      $form['condition'][$i]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => $this->t('Enter some title which helps to identify this record.'),
        '#default_value' => isset($quotes[$i]['title']) ? $quotes[$i]['title'] : NULL,
        '#required' => TRUE,
      ];

      $form['condition'][$i]['weight_from'] = [
        '#type' => 'physical_measurement',
        '#measurement_type' => 'weight',
        '#allow_unit_change' => TRUE,
        '#title' => $this->t('Weight from'),
        '#description' => $this->t('Weight from is always inclusive value.'),
        '#default_value' => isset($quotes[$i]['weight_from']) ? $quotes[$i]['weight_from'] : NULL,
        '#required' => TRUE,
      ];

      $form['condition'][$i]['weight_to'] = [
        '#type' => 'physical_measurement',
        '#measurement_type' => 'weight',
        '#allow_unit_change' => TRUE,
        '#title' => $this->t('Weight to'),
        '#description' => $this->t('Weight from is always exclusive value.'),
        '#default_value' => isset($quotes[$i]['weight_to']) ? $quotes[$i]['weight_to'] : NULL,
        '#required' => TRUE,
      ];

      $form['condition'][$i]['price'] = [
        '#type' => 'commerce_price',
        '#title' => $this->t('Price'),
        '#default_value' => isset($quotes[$i]['price']) ? $quotes[$i]['price'] : NULL,
        '#required' => TRUE,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => array('::addOne'),
      /*
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'conditions-fieldset-wrapper',
      ],
      */
    ];

    if ($value > 1) {
      $form['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => array('::removeCallback'),
        /*
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'conditions-fieldset-wrapper',
        ]
        */
      ];
    }
    $form_state->setCached(FALSE);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('conditions_count');
    $add_button = $name_field + 1;
    $form_state->set('conditions_count', $add_button);
    $form_state->setRebuild();
  }

  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('conditions_count');
    return $form['condition'];
  }

  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('conditions_count');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('conditions_count', $remove_button);
    }
    $form_state->setRebuild();
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = \Drupal::service('config.factory')->getEditable('postbellum.settings');
    $values = $form_state->getValues();
    $config->set('shipping_quotes', $values['condition'])->save();
  }
}
