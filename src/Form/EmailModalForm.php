<?php
/**
* THE WORKING COPY of modal form. 
* TODDO: refactor into one form with multiple displays via block tbd.
*/
namespace Drupal\email_subscriber\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\HtmlCommand;


use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface; // Correct GuzzleHttp ClientInterface


/**
 * Provides a test form with AJAX functionality.
 */
class EmailModalForm extends FormBase {
// Inject it in your constructor:
  protected $renderer;
 protected $configFactory;
 /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
 /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

 /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'emailmodalform';
  }
  
  
  public function __construct(ConfigFactoryInterface $config_factory,MessengerInterface $messenger, Connection $database,ClientInterface $http_client, RendererInterface $renderer) {
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->database = $database;
    $this->httpClient = $http_client;
    $this->renderer = $renderer;
    
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'), 
      $container->get('messenger'),
      $container->get('database'),
      $container->get('http_client'),
      $container->get('renderer')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      
    $form['#prefix'] = '<div id="emailmodalform-messages"></div><div id="emailmodalform-wrapper">';
    $form['#suffix'] = '</div>';

    $form['firstname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
    ];

    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Signup!'),
      '#id' => 'emailmodalform-submit', // Unique ID for the submit button
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
        'wrapper' => 'emailmodalform-messages',
      ],
    ];

    $form['#attached']['library'][] = 'email_subscribe/email_modal_styles';
    $form['#theme'] = 'email_modal_form';
    
    return $form;
  
  }

  
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $firstname = $form_state->getValue('firstname');
    $lastname = $form_state->getValue('lastname');
    $email = $form_state->getValue('email');

    // Set a Drupal status message.
    \Drupal::messenger()->addStatus($this->t('Thank you @firstname @lastname for your submission. We received your email address: @email.', [
      '@firstname' => $firstname,
      '@lastname' => $lastname,
      '@email' => $email,
    ]));

     // ✅  define render array in a variable first.
    $messages = ['#type' => 'status_messages'];
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $rendered_messages = $renderer->renderRoot($messages);
    
    //alternatively add to form so the form always has the message item instead of relying on the system since its not guaranteed name convention.
    $response->addCommand(new HtmlCommand('#emailmodalform-messages', $rendered_messages));


    // Clear the form fields. found in the email-modal emailModalFormReset
    $response->addCommand(new InvokeCommand(NULL, 'emailModalFormReset', ['emailmodalform']));
    
    // Close the modal using JavaScript. this is handled via the email-modal-modal.js file email-form-modal
    $response->addCommand(new InvokeCommand('#email-form-modal', 'modal', ['hide']));
    
    
    return $response;
}

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
    
  }

  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This function is required but not used for AJAX submissions.
    //TODO: 
    $route_match = \Drupal::routeMatch();

    // Get the node from the route.
   // $node = $route_match->getParameter('node');
    
    $values = $form_state->getValues();
    \Drupal::logger('email_subscriber')->info(" Modal email subscriber submit fired.");
    try{
          
          $this->email_subscribe_insert_internal_db($values);
         
          /*
          * handle the hubspot data push here. to initiate this you need a form set up in hubspot to recieve but brevo can push this instead to hubspot.
          * consider this a demo setting.
          */
         // $this->email_subscribe_send_hubspot_values($values);
          /*
          * handle the brevo data push here. set up brevo to be double opt in and asign the url response route to the brevo listener in its workflow.
          */
          //$this->email_subscribe_brevo($values);
          $this->messenger()->addMessage($this->t('<h4>Thank You!</h4> We are so glad you are joining our community!'));
          //version 2 may include mailing out but its usefulness is for customer only really and brevo handles this. if no system is connected then the messages could be used.
          //$this->send_internal_mail($values);

          // deprecated use of Brevo opt in email sent out instead of this email  
          //$this->sendthanksmail($values); // the thank you email to contact who subscribed. 
    } catch(Exception $ex){
        \Drupal::logger('email_subscriber')->error($ex->getMessage());
    }
  }
  
  
  
  
/**
   * Insert data into Internal database.
   */
  public function email_subscribe_insert_internal_db($values) {
          
    // Insert the form data into the custom table.
    try {    
      $result = $this->database->insert('email_subscriber')
            ->fields([
              'ip_address' => \Drupal::request()->getClientIp(),
              'email' => $values['email'],
              'firstname' => $values['firstname'],
              'lastname' => $values['lastname'],
              'created' => \Drupal::time()->getRequestTime(),
              'confirm_status' => 'pending',
              'unsubscribed' => 0,
              'unsubscribe_requested' => 0,
            ])
            ->execute();
  
    }
    catch (Exception $e) {
      // Log the exception to watchdog.
      \Drupal::logger('email_subscriber')
        ->error($e->getMessage());
    }
 
  
  }
  
  
  public function email_subscribe_brevo($values) {
    $config = \Drupal::config('email_subscriber.config_form');
    $basic_auth_access_token = $config->get('brevo_api_id');
    
    $brevo_url = 'https://api.brevo.com/v3/contacts';

    // Prepare the data to send. Sept 04  24 "listIds" = 26 Website Driven Newsletter Signup under brevo settings createa a new listing to receive the sign ups
    $data = [
      'updateEnabled' => true,
      'email' => $values['email'],
      "attributes" => [
        "FIRSTNAME" => $values['firstname'],
        "LASTNAME" => $values['lastname'],
      ],
      "listIds" => [
        26
      ],
    ];


    $config = \Drupal::config('email_subscriber.config_form'); 
    $apikey = $config->get('brevo_api_key');
   /* \Drupal::logger('email_subscriber')->info('API key is: {response}', [
      'response' => substr($apikey, 0, 5) . '****',
    ]);
*/

    try {
      // Send the POST request.
      $response = $this->httpClient->post($brevo_url, [
        'body' => json_encode($data), // JSON-encoded data.
        'headers' => [
          'accept' => 'application/json',
          'api-key' => $apikey , 
          'content-type' => 'application/json',
        ],
      ]);

      // Check the status code.
      $statusCode = $response->getStatusCode();

      // Get the body content.
      $responseBody = $response->getBody()->getContents();

      if ($statusCode == 200 || $statusCode == 201  || $statusCode == 204 ) {
        //$this->messenger()->addStatus($this->t('<h4>Thank You!</h4> We are so glad you are joining our community!'));
        \Drupal::logger('emtp_email_subscribe')->info('Submission post success at Email newsletter form. @response', [
          '@response' => print_r($responseBody, TRUE),
        ]);
      }
      else {
       // $this->messenger()->addError($this->t('Subscription failed. Please try again later.'));
        \Drupal::logger('emtp_email_subscribe')->error('Subscription failed. Unexpected status code received from Brevo API: {status}', [
          '@status' => $statusCode,
        ]);
      }
    }
    catch (\GuzzleHttp\Exception\RequestException $error) {
      $response = $error->getResponse();
      $responseInfo = $response->getBody()->getContents();
      $message = new \Drupal\Component\Render\FormattableMarkup('Brevo API connection error. Error details are as follows:<pre>@response</pre>', [
        '@response' => print_r(json_decode($responseInfo), TRUE),
      ]);
      \Drupal::logger('emtp_email_subscribe')->error('Submission remote post failed at Brevo Guzzle level Email Subscription form @error', [
        '@error' => $message,
      ]);
    }
    catch (\Exception $error) {
      \Drupal::logger('emtp_email_subscribe')->error('An unknown error occurred while trying to connect to the Brevo API: @error', [
        '@error' => $error->getMessage(),
      ]);
    }
  }

  


/*
 * send 
 * 
*/
  public function email_subscribe_send_hubspot_values($values){
      //$email = "crystal.jones@jonezcorp.com";//testing purpose 
      $mode = "modal"; //try to define which user filled in form from newsletter modal or page feature block?
      $host = \Drupal::request()->getSchemeAndHttpHost();
      $data = [
        'fields' => [
            [
              'objectTypeId' => '0-1',
              'name' => 'email',
              'value' => $values['email'],
            ],
            [
              'objectTypeId' => '0-1',
              'name' => 'firstname',
              'value' => $values['firstname'],
            ],
            [
              'objectTypeId' => '0-1',
              'name' => 'lastname',
              'value' => $values['lastname'],
            ],
        ],
        'context' => [
          'pageUri' => $host . '/newsletter-signup/'.$mode,
          'pageName' => 'Newsletter Signup Form',
          'ipAddress' => \Drupal::request()->getClientIp(),
        ],
       ];
       
      
      //Aug9 2024 ch: centralize this authentication token so that when swapping out every 6 months its just configuration.
      $config = \Drupal::config('email_subscriber.config_form');
      $basic_auth_access_token = $config->get('hubspot_basic_auth');
      //todo: centralize this authentication token so that when swapping out every 6 months its just configuration.
        
        
  
      $auth = 'Bearer ' . $basic_auth_access_token;	
      $portalId =$config->get('portal_id');

      $formId= $config->get('hubspot_form_id'); 
      
      
      $hubspot_url = "https://api.hsforms.com/submissions/v3/integration/submit/".$portalId."/".$formId;
      
      try{
        $response = \Drupal::httpClient()->post($hubspot_url, [
          'verify' => true,
          'json' => $data,
          'headers' => [
            'Authorization' => $auth,
            'Content-type' => 'application/json',
          ],
        ])->getBody()->getContents();

        $decoded = json_decode($response, true);
       // $this->messenger()->addStatus($this->t('<h4>Thank You!</h4> '.$decoded["inlineMessage"] .' We are so glad you are joining our community!</div>'));
        \Drupal::logger('emtp_email_subscribe')->info('submission post success! at Email newsletter form', ['operations' => 'HubSpot Remote API Post', 'response' => $decoded['inlineMessage']]);

      } catch (\GuzzleHttp\Exception\GuzzleException $error) {
        // Get the original response
        $response = $error->getResponse();
        // Get the info returned from the remote server.
        $response_info = $response->getBody()->getContents();
        // Using FormattableMarkup allows for the use of <pre/> tags, giving a more readable log item.
        $message = new \Drupal\Component\Render\FormattableMarkup('API connection error. Error details are as follows:<pre>@response</pre>', ['@response' => print_r(json_decode($response_info), TRUE)]);
        // Log the error

        \Drupal::logger('emtp_email_subscribe')->error('submission remote post failed at guzzle level Contact us form', [ '@error'=>$error->getMessage()]);
      }
      catch (\Exception $error) {
        // Log the error.
        //deprecated watchdog_exception('Remote API Connection', $error, t('An unknown error occurred while trying to connect to the remote API. This is not a Guzzle error, nor an error in the remote API, rather a generic local error occurred. The reported error was @error', ['@error' => $error->getMessage()]));
        \Drupal::logger('emtp_email_subscribe')->error('Something errored! ', [ '@error'=>$error->getMessage()]);
      }
            
            
            
  }
  
  /*
  * 
  */

  public function send_internal_mail($values){
    
    $config = \Drupal::config('emtp_forms.settings');
    $internal_subject = $config->get('emtp_email_subscribe.ees_email_internal_subject');
    $internal_template = $config->get('emtp_email_subscribe.ees_email_internal_template');
    //$ees_email_list = $config->get('emtp_email_subscribe.ees_email_list');
    $to = $config->get('emtp_email_subscribe.ees_email_list');
    $keyid = 'emailmodalform';
    $body_message = '';  
    $params['values'] = $values;
    $fullname = $params['values']['firstname'] ." ". $params['values']['lastname'];
    $params['values']['fullname'] = $fullname;
    
    //TODO: add the page url so modal and front page newsletter is within it.
    $subject = str_replace(
          ['@email'],
          [$params['values']['email'] ],
          $internal_subject
      );
      
    $body_message = str_replace(
          ['@subject','@email', '@fullname'],
          [$subject, $params['values']['email'], $params['values']['fullname'] ],
          $internal_template
    );
      
      
    $body = $body_message;
      
      
      // Custom headers. These headers are tested to be the functional ones from webform to get the forms to have proper dmarc and dkim/spf
      //TODO: this may be pointless here as the drupal mail seems to overwrite them tbd if just use module files header manipulation.
      $headers = [
        'MIME-Version' => 1.0 ,
        //'From' => 'EMTP <info@emtp.com>',
        'From' => 'info@emtp.com',
        'Reply-To' => 'info@emtp.com',
        'Return-Path' => 'info@emtp.com',
        'Sender' => 'info@emtp.com',
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        //'Cc' => 'crystal.hansen@emtp.com',  // Optional, only if needed.
      ];
      
      // Fetch site email to ensure it matches domain.
      $from = \Drupal::config('system.site')->get('mail');
      
      // Simplified email parameters.
      $params = [];
      $params['subject'] = $subject;
      $params['body'] = $body;
      $params['headers'] = $headers; // Use headers array.
     
     //TODO: make this use the $this->mailManager ->mail() same function but need different object return in constructor
      $mailManager = \Drupal::service('plugin.manager.mail');
      $status = ($mailManager->mail('emtp_email_subscribe', $keyid, $to, \Drupal::languageManager()->getCurrentLanguage()->getId(), $params, $from) ) ? 
          "E-Mail EMTP: <span style='font-weight:bold;color:#090'>OK</span><hr>" : 
          "E-Mail EMTP: <span style='font-weight:bold;color:#900'>KO</span><hr>";

     //\Drupal::logger('emtp_email_subscribe')->debug('<pre><code>' . print_r($params, TRUE) . '</code></pre>');

     if ($status) {
          setcookie('email', $values['email'], time()+365*24*3600, '/');
          \Drupal::logger('emtp_email_subscribe')->info('Email triggered successfully.');
      } else {
          \Drupal::messenger()->addStatus($this->t($status));
      }

  }
  

   //
   public function sendthanksmail($values){
       
      $config = \Drupal::config('emtp_forms.settings');
      $contact_subject = $config->get('emtp_email_subscribe.ees_email_contact_subject');
      $contact_template = $config->get('emtp_email_subscribe.ees_email_contact_template');
      
     
      $body_message = '';  
      $params['values'] = $values;
      
     //TODO: add the page url so modal and front page newsletter is within it.
      $subject = str_replace(
            ['@firstname'],
            [$params['values']['firstname'] ],
            $contact_subject
        );
        
      $body_message = str_replace(
            ['@subject', '@firstname'],
            [$subject, $params['values']['firstname'] ],
            $contact_template
      );
      
      \Drupal::logger('emtp_email_subscribe')->info('Email triggered successfully.' . $body_message ); 
      
      $body = $body_message;
      $keyid = 'emailmodalform';
      $to = $values['email'];  // $values['firstname'];
      
      // Custom headers. These headers are tested to be the functional ones from webform to get the forms to have proper dmarc and dkim/spf
      //TODO: this may be pointless here as the drupal mail seems to overwrite them tbd if just use module files header manipulation.
      $headers = [
        'MIME-Version' => 1.0 ,
        //'From' => 'EMTP <info@emtp.com>',
        'From' => 'info@emtp.com',
        'Reply-To' => 'info@emtp.com',
        'Return-Path' => 'info@emtp.com',
        'Sender' => 'info@emtp.com',
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        //'Cc' => 'crystal.hansen@emtp.com',  // Optional, only if needed.
      ];
      
      // Fetch site email to ensure it matches domain.
      $from = \Drupal::config('system.site')->get('mail');
      
      // Simplified email parameters.
      $params = [];
      $params['subject'] = $subject;
      $params['body'] = $body;
      $params['headers'] = $headers; // Use headers array.
     
     //TODO: make this use the $this->mailManager ->mail() same function but need different object return in constructor
      $mailManager = \Drupal::service('plugin.manager.mail');
      $status = ($mailManager->mail('emtp_email_subscribe', $keyid, $to, \Drupal::languageManager()->getCurrentLanguage()->getId(), $params, $from) ) ? 
          "E-Mail EMTP: <span style='font-weight:bold;color:#090'>OK</span><hr>" : 
          "E-Mail EMTP: <span style='font-weight:bold;color:#900'>KO</span><hr>";
      
      //\Drupal::logger('emtp_email_subscribe')->debug('<pre><code>' . print_r($params, TRUE) . '</code></pre>');
      
      if ($status) {
          setcookie('email', $values['email'], time()+365*24*3600, '/');
          \Drupal::logger('emtp_email_subscribe')->info('Email triggered successfully.');
      } else {
          \Drupal::messenger()->addStatus($this->t($internal_status));
      }
      

  }


  
  
}
