<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\Support;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;

final class FixtureModuleList extends ModuleExtensionList
{
	/**
	 * Eras whose fixtures a scan returns.
	 *
	 * An info file with no `core_version_requirement` throws on any scan while the switch is off,
	 * so a test sees only its own era.
	 *
	 * @var string[]
	 */
	public static array $eras = [];

	protected function doScanExtensions()
	{
		$extensions = parent::doScanExtensions();
		$base = dirname(__DIR__, 2) . '/fixtures/legacy';
		foreach (self::$eras as $era) {
			foreach (glob("$base/$era/*/*.info.yml") ?: [] as $info) {
				$name = basename($info, '.info.yml');
				$extensions[$name] = new Extension(
					$this->root,
					'module',
					"../../tests/fixtures/legacy/$era/$name/$name.info.yml",
					"$name.module",
				);
			}
		}
		return $extensions;
	}
}
