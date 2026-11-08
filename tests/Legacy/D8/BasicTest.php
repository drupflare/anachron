<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D8;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\InfoParserException;
use Drupal\legacy_d8_basic\LegacyCalls;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Symfony\Component\HttpFoundation\Request;

final class BasicTest extends LegacyTestCase
{
	use UserCreationTrait;

	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(InfoParserException::class);
		$this->container->get('module_installer')->install(['legacy_d8_basic']);
	}

	public function testEnabledWithTheSwitchRecordsTheMissingRequirement(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		self::assertTrue(\Drupal::moduleHandler()->moduleExists('legacy_d8_basic'));
		$info = \Drupal::service('extension.list.module')->getExtensionInfo('legacy_d8_basic');
		self::assertSame('(none)', $info['anachron_core_requirement']);
	}

	public function testGreetRouteAnswersAPermittedUser(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$this->setUpCurrentUser([], ['administer legacy d8']);
		$response = $this->doRequest(Request::create('/legacy-d8/greet'));
		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('Greeting from D8', $this->getRawContent());
	}

	public function testHookHelpAnswersForTheHelpRoute(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$help = \Drupal::moduleHandler()->invoke('legacy_d8_basic', 'help', [
			'help.page.legacy_d8_basic',
			\Drupal::service('current_route_match'),
		]);
		self::assertStringContainsString('Legacy D8 basic module', (string) $help);
	}

	public function testBlockAnnotationIsDiscovered(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$definition = \Drupal::service('plugin.manager.block')->getDefinition(
			'legacy_d8_basic_greeting',
		);
		self::assertSame('Legacy D8 greeting', (string) $definition['admin_label']);
	}

	public function testDrupalSetMessageQueuesAStatusMessage(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$this->calls()->notify();
		$messages = \Drupal::messenger()->messagesByType('status');
		self::assertContains('Greeting queued', array_map('strval', $messages));
	}

	public function testDrupalGetMessagesReturnsTheQueuedStatusMessages(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$this->calls()->notify();
		$queued = $this->calls()->queued();
		self::assertContains('Greeting queued', array_map('strval', $queued['status'] ?? []));
	}

	public function testDrupalGetPathReturnsTheModulePath(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$expected = \Drupal::service('extension.list.module')->getPath('legacy_d8_basic');
		self::assertSame($expected, $this->calls()->modulePath());
	}

	public function testFormatDateRendersTheTimestampInTheGivenFormat(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		self::assertSame('1970-01-01 00:00', $this->calls()->formatDate(0));
	}

	public function testDrupalRenderReturnsTheRenderedMarkup(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		self::assertSame('Rendered text', (string) $this->calls()->render());
	}

	public function testEntityLoadReturnsTheUserForAnId(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		$this->installEntitySchema('user');
		$account = $this->createUser([], 'legacy_d8_reader');
		$loaded = $this->calls()->loadUser((int) $account->id());
		self::assertSame('legacy_d8_reader', $loaded->getAccountName());
	}

	public function testSafeMarkupFormatEscapesItsArguments(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		self::assertSame('&lt;b&gt;x&lt;/b&gt;', (string) $this->calls()->escape('<b>x</b>'));
	}

	public function testEntityManagerReturnsTheUserStorage(): void
	{
		$this->enableLegacy('legacy_d8_basic');
		if (!method_exists(\Drupal::class, 'entityManager')) {
			$this->gap(
				'\Drupal::entityManager(): a static method of core\'s own class, removed in 9.0, so no polyfill can define it; it needs the ingest-time rewrite',
			);
		}
		self::assertInstanceOf(EntityStorageInterface::class, $this->calls()->userStorage());
	}

	private function calls(): LegacyCalls
	{
		return $this->container->get('legacy_d8_basic.legacy_calls');
	}
}
