<?php


/**
 * @file
 * Contains \Drupal\email_subscriber\Plugin\Block.
 */
namespace Drupal\email_subscriber\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;


/**
 * Provides a 'Email Subscriber' Block.
 *
 * @Block(
 *   id = "email_subscriber_block",
 *   admin_label = @Translation(" Email Subscriber Page Block"),
 *   category = @Translation(" Email Subscriber "),
 * )
 */
class EmailSubscribeFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::logger('email_subscriber')->info("email subscribe block fired.");
    $form = \Drupal::formBuilder()->getForm('Drupal\email_subscriber\Form\EmailSubscribeForm'); 
    return $form;
  }

}

