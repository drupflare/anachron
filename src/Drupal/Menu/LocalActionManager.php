<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Menu;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupflare\Anachron\Drupal\Plugin\Derivative\LocalActionDeriver;
use Drupflare\Anachron\Drupal\Plugin\Discovery\InfoHookDeriverDiscovery;

class LocalActionManager extends \Drupal\Core\Menu\LocalActionManager
{
	protected function getDiscovery()
	{
		if (!isset($this->discovery)) {
			$yaml_discovery = new YamlDiscovery(
				'links.action',
				$this->moduleHandler->getModuleDirectories(),
			);
			$yaml_discovery->addTranslatableProperty('title', 'title_context');
			$info_hook_discovery = new InfoHookDeriverDiscovery(
				$yaml_discovery,
				'hook_menu',
				LocalActionDeriver::class,
			);
			$this->discovery = new ContainerDerivativeDiscoveryDecorator($info_hook_discovery);
		}
		return $this->discovery;
	}
}
