<?php

namespace Drupal\postbellum_helpers\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Event\ProductEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\workflows\Transition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PostbellumEventSubscriber
 *
 * @package Drupal\postbellum_helpers\EventSubscriber
 *
 */

class PostbellumEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new PostbellumEventSubscriber object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    MailManagerInterface $mail_manager
  ) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => 'sendEmail',
      ProductEvents::PRODUCT_UPDATE => 'onProductUpdate',
    ];
    return $events;
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onProductUpdate(ProductEvent $event) {
    foreach ($event->getProduct()->getVariations() as $variation) {
      $variation->setTitle($event->getProduct()->getTitle());
      $variation->save();
    }
  }

  /**
   * Sends the email.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event-       *   The transition event.
   */
  public function sendEmail(WorkflowTransitionEvent $event) {
    // Create the email.
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $event->getEntity();
    $to = 'petiar@gmail.com,jana.hrdlickova@postbellum.sk';
    $params = [
      'from' => $order->getStore()->getEmail(),
      'subject' => $this->t(
        'Nová objednávka! Číslo: [#@number]',
        ['@number' => $order->getOrderNumber()]
      ),
      'body' => ['#markup' => $this->t(
        'Na webe máme novú objednávku: http://eshop.postbellum.sk/objednavka/@id.',
        ['@id' => $order->id() ]
      )],
    ];

    // Set the language that will be used in translations.
    if ($customer = $order->getCustomer()) {
      $langcode = $customer->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    // Send the email.
    $this->mailManager->mail('commerce', 'receipt', $to, $langcode, $params);
  }
}
