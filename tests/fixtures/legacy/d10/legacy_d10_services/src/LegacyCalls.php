<?php

namespace Drupal\legacy_d10_services;

use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Core\Serialization\Yaml;
use Drupal\node\NodeForm;

/**
 * Service that calls Drupal 10 classes which Drupal 11 moved or removed.
 */
class LegacyCalls {

  /**
   * Decodes YAML with Drupal\Core\Serialization\Yaml.
   */
  public function parseYaml(string $raw): array {
    return Yaml::decode($raw);
  }

  /**
   * Hashes a password with the Drupal 10 phpass class and checks it back.
   */
  public function hashPassword(string $password): array {
    $hasher = new PhpassHashedPassword(\Drupal::service('password'));
    $hash = $hasher->hash($password);
    return ['prefix' => substr($hash, 0, 3), 'matches' => $hasher->check($password, $hash)];
  }

  /**
   * Builds the Drupal 10 node form object.
   */
  public function buildNodeForm(): string {
    return get_class(NodeForm::create(\Drupal::getContainer()));
  }

}
