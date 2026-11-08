<?php

namespace Drupal\legacy_d8_basic\Controller;

use Drupal\Core\Controller\ControllerBase;

class GreetController extends ControllerBase {

  public function greet() {
    return ['#markup' => t('Greeting from D8')];
  }

}
