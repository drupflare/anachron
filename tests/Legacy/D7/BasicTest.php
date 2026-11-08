<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D7;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Symfony\Component\HttpFoundation\Request;

final class BasicTest extends LegacyTestCase
{
	use UserCreationTrait;

	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(MissingDependencyException::class);
		$this->container->get('module_installer')->install(['legacy_d7_basic']);
	}

	public function testEnabledWithTheSwitchAndTheDeclaredCoreIsKept(): void
	{
		$this->enableLegacy('legacy_d7_basic');
		self::assertTrue(\Drupal::moduleHandler()->moduleExists('legacy_d7_basic'));
		$info = \Drupal::service('extension.list.module')->getExtensionInfo('legacy_d7_basic');
		self::assertSame('^7', $info['anachron_core_requirement']);
	}

	public function testMenuCallbackWithAnAccessCallbackTrueAnswers(): void
	{
		$this->enableLegacy('legacy_d7_basic');
		$response = $this->doRequest(Request::create('/legacy-d7/open'));
		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('open page', $this->getRawContent());
	}

	public function testPermissionGuardedCallbackDeniesThenAnswersAndCountsWithVariables(): void
	{
		$this->enableLegacy('legacy_d7_basic');

		$this->setUpCurrentUser();
		try {
			$denied = $this->doRequest(Request::create('/legacy-d7/hello'));
			self::assertSame(403, $denied->getStatusCode());
		} catch (\Throwable $e) {
			self::assertStringContainsString('AccessDenied', get_class($e));
		}

		$this->setUpCurrentUser([], ['administer legacy d7']);
		$response = $this->doRequest(Request::create('/legacy-d7/hello'));
		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('Hello from D7, visit 1', $this->getRawContent());
		self::assertSame(1, variable_get('legacy_d7_basic_visits'));

		$this->doRequest(Request::create('/legacy-d7/hello'));
		self::assertSame(2, variable_get('legacy_d7_basic_visits'));
	}
}
