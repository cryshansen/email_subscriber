<?php

/**
* This file is to handle the user accept subscription email that is sent from Brevo.
* the button link generated in Brevo matching pattern: /signup/{contact.EMAIL} comes to site.com/signup/emailaddress route lands here and 
* Has a basic thank you for subscribing so that it is not a page not found. It may not be necessary for brevo to consider the user 'clicked' following their tutorial
*/

namespace Drupal\email_subscriber\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscribeController extends ControllerBase {

  /**
   * Handles subscription confirmation.
   */
  public function confirmSignup($emailaddress, Request $request) {
    // Perform validation of the email address if necessary.
    if (!filter_var($emailaddress, FILTER_VALIDATE_EMAIL)) {
      // If the email is invalid, show an error page.
      return new Response('Our system didnt like something. 403 Bad Request.', 403);
    }

    // Lookup the subscriber in your custom subscription database or table.
    $subscriber = $this->getSubscriberByEmail($emailaddress);
    if (!$subscriber || $subscriber->confirm_status !== 'pending') {
      // If the subscriber does not exist or is already confirmed.
     return new Response('Hey, we didnt recognize this!', 200);
    }

    // Mark the subscriber as confirmed.
    $this->confirmSubscriber($emailaddress);
    // Set a thank you message to display to the user.
    \Drupal::messenger()->addMessage($this->t('Thanks for subscribing! Your email %email has been successfully confirmed.', ['%email' => $emailaddress]));

    // Redirect to a "thank you" page or show a success message.
    //return new RedirectResponse('/thank-you-for-signing-up');
    
     // Return a confirmation page or render the message.
    return [
      '#markup' => $this->t('You have successfully subscribed to our newsletter with %email.', ['%email' => $emailaddress]),
    ];
    
  }

  /**
   * Helper function to get the subscriber by email address.
   */
  protected function getSubscriberByEmail($emailaddress) {
    // Replace this with actual logic to retrieve the subscriber from the database.
    $query = \Drupal::database()->select('email_subscriber', 'e')
      ->fields('e', ['email', 'confirm_status'])
      ->condition('email', $emailaddress)
      ->execute();
    return $query->fetchObject();
  }

  /**
   * Helper function to mark a subscriber as confirmed.
   */
  protected function confirmSubscriber($emailaddress) {
	  $action_time = \Drupal::time()->getRequestTime(); // Unix timestamp
    // Update the database to mark the subscriber as confirmed.
    \Drupal::database()->update('email_subscriber')
      ->fields(['confirm_status' =>'confirmed'])
      ->fields(['updated' => $action_time ])
      ->condition('email', $emailaddress)
      ->execute();
  }
  
  
  
  

}
