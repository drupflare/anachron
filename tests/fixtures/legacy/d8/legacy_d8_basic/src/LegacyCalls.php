<?php

namespace Drupal\legacy_d8_basic;

use Drupal\Component\Utility\SafeMarkup;

class LegacyCalls {

  public function notify() {
    drupal_set_message(t('Greeting queued'));
  }

  public function queued() {
    return drupal_get_messages('status', FALSE);
  }

  public function modulePath() {
    return drupal_get_path('module', 'legacy_d8_basic');
  }

  public function formatDate($timestamp) {
    return format_date($timestamp, 'custom', 'Y-m-d H:i', 'UTC');
  }

  public function render() {
    $element = ['#markup' => 'Rendered text'];
    return drupal_render($element);
  }

  public function loadUser($uid) {
    return entity_load('user', $uid);
  }

  public function escape($text) {
    return SafeMarkup::format('@text', ['@text' => $text]);
  }

  public function userStorage() {
    return \Drupal::entityManager()->getStorage('user');
  }

}
