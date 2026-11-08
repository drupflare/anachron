<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Routing;

use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;

final class HookAdminPathsTest extends IntegrationTestCase
{
	protected static $modules = ['system'];

	public function testAdminPaths(): void
	{
		$this->container->get('router.builder')->rebuild();
		$paths = path_get_admin_paths();
		self::assertStringContainsString('/admin/content', $paths['admin']);
		self::assertStringContainsString('/admin/reports/status/php', $paths['non_admin']);
	}
}
