<?php

/**
 * @file
 * Contains \Drupal\email_subscriber\Form\EmailSubscribeConfigForm.
 */
namespace Drupal\email_subscriber\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Class EmailSubscribeConfigForm.
 */
class EmailSubscribeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['email_subscriber.config_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_subscribe_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_subscriber.config_form');
    //for hubspot functionality the two below are required fields setup requires hubspot forms to link the two systems to the contacts of hubspot and drupal forms
   
    $form['portal_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hubspot portal id'),
      '#description' => $this->t('Enter your Hubspot Portal Id.'),
      '#default_value' => $config->get('portal_id'),
      '#required' => TRUE,
    ];
    $form['hubspot_form_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('form id'),
      '#description' => $this->t('Enter your hubspot form id.'),
      '#default_value' => $config->get('hubspot_form_id'),
      '#required' => TRUE,
    ];
   
    
    // this scope has content and connection preferences scopes. requires content for the connection preferences https://api.hubapi.com/email/public/v1/subscriptions?portalId=23440935 to succeed.  
   $form['hubspot_unsubscribe_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hubspot Unsubscribe API Key'),
      '#description' => $this->t('Enter your Hubspot communication preferences API key.'),
      '#default_value' => $config->get('hubspot_unsubscribe_api_key'),
      '#required' => TRUE,
    ];

     $form['brevo_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brevo API Key'),
      '#description' => $this->t('Enter your Brevo API key.'),
      '#default_value' => $config->get('brevo_api_key'),
      '#required' => TRUE,
    ];
    
    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('email_subscriber.config_form')
      ->set('brevo_api_key', $form_state->getValue('brevo_api_key'))
      ->set('hubspot_unsubscribe_api_key', $form_state->getValue('hubspot_unsubscribe_api_key'))
       ->set('hubspot_form_id', $form_state->getValue('hubspot_form_id'))
      ->set('portal_id', $form_state->getValue('portal_id'))
      ->save();
  }
}
