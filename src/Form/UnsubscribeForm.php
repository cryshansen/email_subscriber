<?php

namespace Drupal\email_subscriber\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use Exception;



/**
 * Provides an Email subscription form with AJAX functionality.
 * this is not to be used deprecated!!!!!
 */
class UnsubscribeForm extends FormBase {

  protected $configFactory;
  protected $messenger;
  protected $httpClient;
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_unsubscribe_form';
  }

  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, Connection $database, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->database = $database;
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), 
      $container->get('messenger'),
      $container->get('database'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
   
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Unsubscribe Email'),
      '#required' => TRUE,
    ];

    $form['channel'] = [
      '#type' => 'hidden',
      '#value' => "Email",
    ];

    $form['subscription_type'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the checkbox for unsubscribing communications from this website.'),
      '#options' => [
        'one-to-one-opted-out' => $this->t('Sales (One-to-one) Opted-out'),
        'marketing-opted-out' => $this->t('Newsletter emails Opted-out'),
      ],
      '#required' => TRUE,
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Opt out'),
      '#id' => 'email-subscribe-form-submit',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'email_subscribe/email_unsubscribe';
    $form['#theme'] = 'email_unsubscribe_form';
    

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $email = $form_state->getValue('email');

    $this->messenger->addStatus($this->t('We received your email address to unsubscribe: @email. This may take up to 24 hours to complete.', ['@email' => $email]));

    $response->addCommand(new HtmlCommand('#status-messages', ['#type' => 'status_messages']));
   // Call the emailUnsubscribeFormReset JS function and pass the form ID.
    $response->addCommand(new InvokeCommand(null, 'emailUnsubscribeFormReset', ['email-unsubscribe-form']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::logger('email_subscriber')->info("Unsubscribe form in email_subscriber form submit was fired.");
    
    try {
      $this->email_subscribe_insert_internal_db($values);
      //$this->email_unsubscribe_send_hubspot_values($values);
    } catch (Exception $ex) {
      \Drupal::logger('email_subscriber')->error($ex->getMessage());
    }
  }

  public function email_subscribe_insert_internal_db($values) {
    $email = $values['email'];
    $ip_address = \Drupal::request()->getClientIp();
    $channel = $values['channel']; // Checkbox value for the channel field
    $action = 'unsubscribe';
    $status = 'opted-out';
    $action_time = \Drupal::time()->getRequestTime(); // Unix timestamp
    $source = 'website email unsubscribe link';
   
    try {
      $connection = $this->database;

      // Check if email exists in the table
      $query = $connection->select('email_subscriber', 'n')
        ->fields('n', ['email'])
        ->condition('email', $email)
        ->execute()
        ->fetchField();

      if ($query) {
        // Update status
        $connection->update('email_subscriber')
          ->fields([
            'unsubscribed' => 1,
            'unsubscribe_requested' => $action_time,
          ])
          ->condition('email', $email)
          ->execute();

      // Insert the action log into the subscription_actions table
      $connection->insert('email_subscribe_subscription_actions')
        ->fields([
          'email' => $email,
          'ip_address' => $ip_address,
          'channel' => 'Email', // Assuming "Email" by default
          'subscriptionId' => 65152031, // Default subscription ID "Marketing Information"  65167030 for One-to-one sales subscription.
          'status' => $status,
          'action' => $action,
          'action_time' => $action_time,
          'source' => $source,
          'legal_basis' => "LEGITIMATE_INTEREST_PQL", // Add if necessary confusing what this represents there are many values to choose from.
          'details' => "Unsubscribed from subscriptions sent from Brevo and unsubscribe from website was requested.",     // legal basis explanation hubspot field
        ])
        ->execute();

        $this->messenger->addStatus($this->t('You have successfully unsubscribed.'));
      } else {
        $this->messenger->addError($this->t('Email not found in the subscription list.'));
      }
    } catch (Exception $e) {
      \Drupal::logger('email_subscriber')->error($e->getMessage());
    }
  }

  /**
  * send hubspot notification of the contact unsubscribe request.
  * used if using this form to unsubscribe from both systems. 
  * TODO: how to align the email into the submission so that it will auto fill the way brevo does it
  */
  
  public function email_unsubscribe_send_hubspot_values($values){
      //$email = "crystal.jones@jonezcorp.com";//testing purpose 
      $mode = "page_block"; //try to define which user filled in form from newsletter modal or page feature block?
      $host = \Drupal::request()->getSchemeAndHttpHost();
 
      
      // Sept 13 
      $config = \Drupal::config('email_subscriber.config_form');
      $basic_auth_access_token = $config->get('hubspot_basic_auth'); //Bearer ######-#####

      $auth = 'Bearer ' . $basic_auth_access_token;
      $hubspot_url = "https://api.hubapi.com/communication-preferences/v3/unsubscribe";
      $request_payload = [
          'emailAddress' => $values['email'],
          'legal_basis' => 'LEGITIMATE_INTEREST_PQL',
          'subscriptionId' => '65152031',
          'legal_basis_explanation' => 'Unsubscribing from newsletter subscription.', //A more detailed explanation to go with the legal basis (required for GDPR enabled portals).
      ];
      try{
        $response = \Drupal::httpClient()->post($hubspot_url, [
          'verify' => true,
          'json' => $request_payload,
          'headers' => [
            'Authorization' => $auth,
            'Content-type' => 'application/json',
          ],
        ])->getBody()->getContents();

        $decoded = json_decode($response, true);
       // $this->messenger()->addStatus($this->t('<h4>Thank You!</h4> '.$decoded["inlineMessage"] .' We are so glad you are joining our community!</div>'));
        \Drupal::logger('email_subscriber')->info('Unsubscribe submission post success! at newsletter Unsubscribe form @operations, @response', ['@operations' => 'HubSpot Communication Preferenceds Remote API Post', '@response' => $decoded['inlineMessage']]);

      } catch (\GuzzleHttp\Exception\GuzzleException $error) {
        // Get the original response
        // Log the error

        \Drupal::logger('email_subscriber')->error('submission remote post failed at guzzle level Unsubscribe form @error', [ '@error'=>$error->getMessage()]);
      }
      catch (\Exception $error) {
        // Log the error.
         \Drupal::logger('email_subscriber')->error('Something errored! @error', [ '@error'=>$error->getMessage() ]);
      }
            
            
            
  }
  
   /**
  * send brevo notification of the contact unsubscribe request.
  * used if using this form to unsubscribe from both systems. 
  * TODO: how to align the email into the submission so that it will auto fill the way brevo does it?
  */
  public function email_unsubscribe_brevo($values) {
      
    $brevo_url = 'https://api.brevo.com/v3/contacts';

   // Prepare the data to send. Sept 04  24 "listIds" = 26 Website Driven Newsletter Signup  13 == unsubscribe list
   $data = [
      'updateEnabled' => true,
      'email' => $values['email'],
      "emailBlacklisted"=>  false,
      "smsBlacklisted"=>  false,
      "listIds"=>  [
          13
      ],
      
    ];

    // Sept 4 2024 add the configuration api key to the code. 
    $config = \Drupal::config('emtp_email_subscribe.config_form');
    $apikey = $config->get('brevo_api_key');
    

    try {
      // Send the POST request.
      $response = $this->httpClient->post($brevo_url, [
        'body' => json_encode($data), // JSON-encoded data.
        'headers' => [
          'accept' => 'application/json',
          'api-key' => $apikey, 
          'content-type' => 'application/json',
        ],
      ]);

      // Check the status code.
      $statusCode = $response->getStatusCode();

      // Get the body content.
      $responseBody = $response->getBody()->getContents();

      if ($statusCode == 200 || $statusCode == 201  || $statusCode == 204 ) {
        
        \Drupal::logger('email_subscriber')->info('Unsubscribe Submission post success at Unsubscribe form.{response}', [
          'response' => print_r($responseBody, TRUE),
        ]);
      }
      else {
       // $this->messenger()->addError($this->t('Subscription failed. Please try again later.'));
        \Drupal::logger('email_subscriber')->error('Unsubscribe Submission post failed. Unexpected status code received from Brevo API: {status}', [
          '@status' => $statusCode,
        ]);
      }
    }
    catch (\GuzzleHttp\Exception\RequestException $error) {
      $response = $error->getResponse();
      $responseInfo = $response->getBody()->getContents();
      $message = new \Drupal\Component\Render\FormattableMarkup('Brevo API guzzel connection error. Error details are as follows:<pre>@response</pre>', [
        '@response' => print_r(json_decode($responseInfo), TRUE),
      ]);
      \Drupal::logger('email_subscriber')->error('Guzzel Brevo API connection error Submission remote post failed at Brevo Guzzle level Email Subscription form {error}', [
        '@error' => $message,
      ]);
    }
    catch (\Exception $error) {
      \Drupal::logger('email_subscriber')->error('An unknown error occurred while trying to connect to the Brevo API: @error', [
        '@error' => $error->getMessage(),
      ]);
    }
  }
  
  
  
}
