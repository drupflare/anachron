<?php

namespace Drupal\legacy_d9_basic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends FormBase {

  public function getFormId() {
    return 'legacy_d9_basic_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['greeting'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Greeting'),
      '#default_value' => 'Hello',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('legacy_d9_basic.greeting', $form_state->getValue('greeting'));
  }

}
