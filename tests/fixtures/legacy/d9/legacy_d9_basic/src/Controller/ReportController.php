<?php

namespace Drupal\legacy_d9_basic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends ControllerBase {

  public function path() {
    return new Response(drupal_get_path('module', 'legacy_d9_basic'));
  }

  public function size($bytes) {
    return new Response((string) format_size((int) $bytes));
  }

  public function render() {
    $element = [
      '#markup' => 'alpha',
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    return new Response(render($element));
  }

  public function theme() {
    $element = [
      '#theme' => 'legacy_d9_basic_list',
      '#items' => ['alpha', 'beta'],
    ];
    return new Response(render($element));
  }

  public function password() {
    return new Response(\Drupal::service('legacy_d9_basic.users')->password(12));
  }

  public function roles() {
    return new Response(implode(',', \Drupal::service('legacy_d9_basic.users')->memberRoleIds()));
  }

}
