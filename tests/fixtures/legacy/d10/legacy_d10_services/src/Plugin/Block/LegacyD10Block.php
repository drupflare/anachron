<?php

namespace Drupal\legacy_d10_services\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with a fixed line of text.
 *
 * @Block(
 *   id = "legacy_d10_services_block",
 *   admin_label = @Translation("Legacy D10 services block"),
 * )
 */
class LegacyD10Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'legacy block'];
  }

}
