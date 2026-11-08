<?php

namespace Drupal\legacy_d9_files\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockBase;

/**
 * Shows how many files the controller has saved.
 *
 * @Block(
 *   id = "legacy_d9_files_latest",
 *   admin_label = @Translation("Saved files"),
 * )
 */
class LatestFileBlock extends BlockBase {

  public function build() {
    $count = \Drupal::database()->select('legacy_d9_files_log', 'l')
      ->countQuery()
      ->execute()
      ->fetchField();
    return ['#markup' => (string) $count];
  }

}
