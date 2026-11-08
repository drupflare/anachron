<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D10;

use Drupal;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Psr\Log\LoggerInterface;
use Stringable;
use Symfony\Component\HttpFoundation\Request;

final class ApiTest extends LegacyTestCase
{
	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(MissingDependencyException::class);
		$this->container->get('module_installer')->install(['legacy_d10_api']);
	}

	public function testEnabledWithTheSwitchAndTheDeclaredCoreIsKept(): void
	{
		$this->enableLegacy('legacy_d10_api');
		self::assertTrue(Drupal::moduleHandler()->moduleExists('legacy_d10_api'));
		$info = Drupal::service('extension.list.module')->getExtensionInfo('legacy_d10_api');
		self::assertSame('^10', $info['anachron_core_requirement']);
	}

	public function testHelpHookAnswersOnItsRoute(): void
	{
		$this->enableLegacy('legacy_d10_api');
		$help = Drupal::moduleHandler()->invoke('legacy_d10_api', 'help', [
			'help.page.legacy_d10_api',
			Drupal::routeMatch(),
		]);
		self::assertStringContainsString('Calls helpers from the Drupal 10 API.', (string) $help);
	}

	public function testFormatSize(): void
	{
		$this->enableLegacy('legacy_d10_api');
		$response = $this->doRequest(Request::create('/legacy-d10/format-size'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame(['size' => '1 KB'], json_decode((string) $response->getContent(), true));
	}

	public function testWatchdogException(): void
	{
		$this->enableLegacy('legacy_d10_api');
		$logger = new class implements LoggerInterface {
			use RfcLoggerTrait;

			public array $records = [];

			public function log($level, string|Stringable $message, array $context = []): void
			{
				$this->records[] = [$level, (string) $message, $context];
			}
		};
		Drupal::service('logger.factory')->addLogger($logger);
		$response = $this->doRequest(Request::create('/legacy-d10/watchdog'));
		self::assertSame(200, $response->getStatusCode());
		$record = null;
		foreach ($logger->records as $candidate) {
			if (($candidate[2]['channel'] ?? '') === 'legacy_d10_api') {
				$record = $candidate;
			}
		}
		self::assertNotNull($record);
		self::assertSame(RfcLogLevel::ERROR, $record[0]);
		self::assertSame('Feed fetch failed: @message', $record[1]);
		self::assertSame('feed unreachable', $record[2]['@message']);
		self::assertSame('RuntimeException', $record[2]['%type']);
	}

	public function testModuleLoadInclude(): void
	{
		$this->enableLegacy('legacy_d10_api');
		$response = $this->doRequest(Request::create('/legacy-d10/module-load-include'));
		self::assertSame(200, $response->getStatusCode());
		$data = json_decode((string) $response->getContent(), true);
		self::assertSame('legacy_d10_api.admin.inc', $data['file']);
		self::assertSame('admin include', $data['answer']);
	}

	public function testModuleLoadIncludeReturnsFalseForAMissingFile(): void
	{
		$this->enableLegacy('legacy_d10_api');
		self::assertFalse(module_load_include('inc', 'legacy_d10_api', 'nope'));
	}

	public function testUserRoleNames(): void
	{
		$this->enableLegacy('legacy_d10_api');
		Role::create(['id' => RoleInterface::ANONYMOUS_ID, 'label' => 'Anonymous'])->save();
		Role::create(['id' => 'editor', 'label' => 'Editor'])->save();
		$response = $this->doRequest(Request::create('/legacy-d10/user-role-names'));
		self::assertSame(
			['anonymous' => 'Anonymous', 'editor' => 'Editor'],
			json_decode((string) $response->getContent(), true),
		);
		self::assertSame(['editor' => 'Editor'], user_role_names(true));
		self::assertSame([], user_role_names(false, 'no such permission'));
	}

	public function testThemeGetRegistry(): void
	{
		$this->gap(
			'theme_get_registry: Call to undefined function ' .
				'Drupal\legacy_d10_api\Controller\theme_get_registry()',
		);
	}

	public function testSystemTimeZones(): void
	{
		$this->gap(
			'system_time_zones: Call to undefined function ' .
				'Drupal\legacy_d10_api\Controller\system_time_zones()',
		);
	}
}
