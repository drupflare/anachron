<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Template;

use Drupflare\Anachron\Tests\Drupal\Utils\RequestTrait;
use Drupflare\Anachron\Tests\Drupal\Utils\TestHttpKernelTrait;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;

final class HookProcessTest extends IntegrationTestCase
{
	use RequestTrait;
	use TestHttpKernelTrait;

	/**
	 * @var string[]  */
	protected static $modules = ['system'];

	protected static function getTestModules(): array
	{
		return ['retrofit_fixtures'];
	}

	public function testHookProcess(): void
	{
		$this->doRequest(Request::create('/retrofit/attached_js_setting'));
		self::assertStringContainsString(
			'hook_process="retrofit_fixtures_process_html"',
			$this->getRawContent(),
		);
	}
}
