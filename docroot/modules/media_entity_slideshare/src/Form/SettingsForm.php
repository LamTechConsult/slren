<?php

namespace Drupal\media_entity_slideshare\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Administration form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_slideshare_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_entity_slideshare.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('media_entity_slideshare.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key.'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Set this to the API Key that SlideShare has provided for you.'),
      '#required' => TRUE,
    ];

    $form['shared_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared secret.'),
      '#default_value' => $config->get('shared_secret'),
      '#description' => $this->t('Set this to the SHA1 hash of the concatenation of the shared secret and the timestamp.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo: Check values with API.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('media_entity_slideshare.settings');
    $keys = [
      'api_key',
      'shared_secret',
    ];

    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    $config->save();
  }

}
