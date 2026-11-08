<?php

namespace Drupal\legacy_d10_services\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form with one static line.
 */
class LegacyD10Form extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legacy_d10_services_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['note'] = ['#markup' => 'legacy form'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
