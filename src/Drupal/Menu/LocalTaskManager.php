<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Menu;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupflare\Anachron\Drupal\Plugin\Derivative\LocalTaskDeriver;
use Drupflare\Anachron\Drupal\Plugin\Discovery\InfoHookDeriverDiscovery;

class LocalTaskManager extends \Drupal\Core\Menu\LocalTaskManager
{
	protected function getDiscovery()
	{
		if (!isset($this->discovery)) {
			$yaml_discovery = new YamlDiscovery(
				'links.task',
				$this->moduleHandler->getModuleDirectories(),
			);
			$yaml_discovery->addTranslatableProperty('title', 'title_context');
			$info_hook_discovery = new InfoHookDeriverDiscovery(
				$yaml_discovery,
				'hook_menu',
				LocalTaskDeriver::class,
			);
			$this->discovery = new ContainerDerivativeDiscoveryDecorator($info_hook_discovery);
		}
		return $this->discovery;
	}
}
