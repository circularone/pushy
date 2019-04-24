<?php

namespace Drupal\pushy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pushy\PushMessages;

/**
 * Class Settings.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pushy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pushy.settings');

    // \Drupal::service('pushy.send')->sendNotification();
    \Drupal::service('pushy.expo.send')->sendNotification();
    $form['ios'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('IOS'),
    ];

    $form['ios']['ios_development_apns_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Development APNS Url'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('ios_development_apns_url'),
    ];

    $form['ios']['ios_development_apns_certificate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Development APNS Certificate'),
      '#description' => $this->t('Absolute path to the pem file'),
      '#default_value' => $config->get('ios_development_apns_certificate'),
    ];

    $form['ios']['ios_production_apns_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Production APNS Url'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('ios_production_apns_url'),
    ];

    $form['ios']['ios_production_apns_certificate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Production APNS Certificate'),
      '#description' => $this->t('Absolute path to the pem file'),
      '#default_value' => $config->get('ios_production_apns_certificate'),
    ];

    $form['android'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Android'),
    ];

    $form['android']['android_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Access Key'),
      '#description' => $this->t('Android API access key'),
      '#default_value' => $config->get('android_api_key'),
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

    $this->config('pushy.settings')
      ->set('ios_development_apns_url', $form_state->getValue('ios_development_apns_url'))
      ->set('ios_development_apns_certificate', $form_state->getValue('ios_development_apns_certificate'))
      ->set('ios_production_apns_url', $form_state->getValue('ios_production_apns_url'))
      ->set('ios_production_apns_certificate', $form_state->getValue('ios_production_apns_certificate'))
      ->set('android_api_key', $form_state->getValue('android_api_key'))
      ->save();
  }

}
