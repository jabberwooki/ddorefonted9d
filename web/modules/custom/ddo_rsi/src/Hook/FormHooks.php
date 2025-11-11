<?php

namespace Drupal\ddo_rsi\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Implements form hooks.
 */
class FormHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $form_ids = ['node_show_form', 'node_show_edit_form'];
    if (!in_array($form_id, $form_ids)) {
      return;
    }

    $form['field_rsi_ident']['#disabled'] = TRUE;
  }

}
