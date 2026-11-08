<?php

namespace Drupal\legacy_d8_basic\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a greeting block.
 *
 * @Block(
 *   id = "legacy_d8_basic_greeting",
 *   admin_label = "Legacy D8 greeting",
 * )
 */
class GreetingBlock extends BlockBase {

  public function build() {
    return ['#markup' => t('Greeting from D8')];
  }

}
