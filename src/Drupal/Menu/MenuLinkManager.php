<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Menu;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupflare\Anachron\Drupal\Plugin\Derivative\MenuLinkDeriver;
use Drupflare\Anachron\Drupal\Plugin\Discovery\InfoHookDeriverDiscovery;

final class MenuLinkManager extends \Drupal\Core\Menu\MenuLinkManager
{
	protected function getDiscovery()
	{
		if (!isset($this->discovery)) {
			$yaml_discovery = new YamlDiscovery(
				'links.menu',
				$this->moduleHandler->getModuleDirectories(),
			);
			$yaml_discovery->addTranslatableProperty('title', 'title_context');
			$yaml_discovery->addTranslatableProperty('description', 'description_context');
			$info_hook_discovery = new InfoHookDeriverDiscovery(
				$yaml_discovery,
				'hook_menu',
				MenuLinkDeriver::class,
			);
			$this->discovery = new ContainerDerivativeDiscoveryDecorator($info_hook_discovery);
		}
		return $this->discovery;
	}
}
