<?php


/**
 * @file
 * Contains \Drupal\email_subscriber\Plugin\Block.
 */
namespace Drupal\email_subscriber\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'EmailSubscribeModalBlock' block.
 *
 * @Block(
 *   id = "email_subscriber_modal_block",
 *   admin_label = @Translation("Modal Email Subscriber Block")
 * )
 */
class EmailSubscribeModalBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
        \Drupal::logger('email_subscriber')->info("Modal Email Subscriber Block fired.");
    // Return the render array of the form
    return \Drupal::formBuilder()->getForm('Drupal\email_subscriber\Form\EmailModalForm');
    
  }

}
