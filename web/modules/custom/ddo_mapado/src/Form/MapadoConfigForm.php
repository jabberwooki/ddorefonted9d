<?php

namespace Drupal\ddo_mapado\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MapadoConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ddo_mapado.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mapado_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ddo_mapado.settings');

    $form['enable_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable synchronization'),
      '#description' => $this->t('Enable data synchronization between domaine d\O website and Mapado ticketing website'),
      '#default_value' => $config->get('enable_sync'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('ddo_mapado.settings')
      ->set('enable_sync', $form_state->getValue('enable_sync'))
      ->save();
  }
}
