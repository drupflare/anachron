<?php

namespace Drupal\legacy_d10_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Page callbacks that call the legacy service.
 */
class LegacyServicesController extends ControllerBase {

  /**
   * Decodes a small YAML document.
   */
  public function yaml() {
    $calls = \Drupal::service('legacy_d10_services.legacy_calls');
    return new JsonResponse($calls->parseYaml("name: legacy\ncount: 3\n"));
  }

  /**
   * Hashes and checks a password.
   */
  public function password() {
    $calls = \Drupal::service('legacy_d10_services.legacy_calls');
    return new JsonResponse($calls->hashPassword('correct horse'));
  }

  /**
   * Builds the node form.
   */
  public function nodeForm() {
    $calls = \Drupal::service('legacy_d10_services.legacy_calls');
    return new JsonResponse(['form' => $calls->buildNodeForm()]);
  }

}
