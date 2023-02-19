<?php

namespace Drupal\commerce_trustpay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_trustpay\TrustpayRequestGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommerceTrustpayBankTransfersPaymentForm extends PaymentOffsiteForm {

  use StringTranslationTrait;

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $payment_gateway = $this->getEntity()->getPaymentGateway();
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;


    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    $trustpayRequestGenerator = new TrustpayRequestGenerator($order);
    $url = $trustpayRequestGenerator->generateRequestUrl($payment_gateway);

    $form['#attached']['library'][] = 'commerce_trustpay/checkout';
    $form['#attached']['drupalSettings']['commerce_trustpay']['url'] = $url;

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Pay via Trustpay'),
      '#attributes' => [
        'class' => ['show-popup']
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromUri($form['#cancel_url']),
      '#attributes' => [
        'class' => ['show-popup']
      ],
    ];

    // No need to call buildRedirectForm(), as we embed an iframe.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }
}
