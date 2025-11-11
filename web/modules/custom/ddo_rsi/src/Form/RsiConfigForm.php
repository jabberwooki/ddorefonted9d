<?php

namespace Drupal\ddo_rsi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ressources SI settings form.
 */
class RsiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ddo_rsi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rsi_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ddo_rsi.settings');

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de base'),
      '#description' => $this->t('URL de base avec barre oblique finale, par exemple <code>https://billeterie.domainedo.fr/</code>.'),
      '#default_value' => $config->get('base_url'),
    ];

    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Identifiant (<em>login</em>)'),
      '#default_value' => $config->get('login'),
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mot de passe'),
      '#default_value' => $config->get('password'),
    ];

    $form['client'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Numéro de client'),
      '#default_value' => $config->get('client'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!str_ends_with($form_state->getValue('base_url'), '/')) {
      $form_state->setErrorByName('base_url', $this->t('Missing trailing slash in URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('ddo_rsi.settings')
      ->set('base_url', $form_state->getValue('base_url'))
      ->set('login', $form_state->getValue('login'))
      ->set('password', $form_state->getValue('password'))
      ->set('client', $form_state->getValue('client'))
      ->save();
  }

}
