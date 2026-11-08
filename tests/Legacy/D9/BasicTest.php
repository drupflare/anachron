<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy\D9;

use Drupal\Core\Extension\MissingDependencyException;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupflare\Anachron\Tests\Legacy\LegacyTestCase;
use Symfony\Component\HttpFoundation\Request;

final class BasicTest extends LegacyTestCase
{
	public function testCoreRefusesItWithoutTheSwitch(): void
	{
		$this->expectException(MissingDependencyException::class);
		$this->container->get('module_installer')->install(['legacy_d9_basic']);
	}

	public function testEnabledWithTheSwitchAndTheDeclaredCoreIsKept(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		self::assertTrue(\Drupal::moduleHandler()->moduleExists('legacy_d9_basic'));
		$info = \Drupal::service('extension.list.module')->getExtensionInfo('legacy_d9_basic');
		self::assertSame('^9', $info['anachron_core_requirement']);
	}

	public function testSettingsFormRendersItsField(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$response = $this->doRequest(Request::create('/legacy-d9/settings'));
		self::assertSame(200, $response->getStatusCode());
		self::assertStringContainsString('name="greeting"', $this->getRawContent());
	}

	public function testDrupalGetPathReturnsTheDirectoryTheExtensionListKnows(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$response = $this->doRequest(Request::create('/legacy-d9/path'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame(
			\Drupal::service('extension.list.module')->getPath('legacy_d9_basic'),
			$this->getRawContent(),
		);
	}

	public function testFormatSizeAnswersInKilobytes(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$this->doRequest(Request::create('/legacy-d9/size/2048'));
		self::assertSame('2 KB', $this->getRawContent());
	}

	public function testRenderAddsPrefixAndSuffixToMarkup(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$this->doRequest(Request::create('/legacy-d9/render'));
		self::assertSame('<p>alpha</p>', $this->getRawContent());
	}

	public function testThemeFunctionKeyRendersTheHookThroughRender(): void
	{
		$this->gap(
			'hook_theme function key: Twig\Error\LoaderError: Template "../../tests/fixtures/legacy/d9/legacy_d9_basic/templates/legacy-d9-basic-list.html.twig" is not defined',
		);
	}

	public function testUserPasswordHasTheRequestedLength(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$this->doRequest(Request::create('/legacy-d9/password'));
		self::assertSame(12, strlen($this->getRawContent()));
		self::assertSame(10, strlen(user_password()));
	}

	public function testUserRolesWithMembersOnlyDropsAnonymous(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		Role::create(['id' => RoleInterface::ANONYMOUS_ID, 'label' => 'Anonymous'])->save();
		Role::create(['id' => RoleInterface::AUTHENTICATED_ID, 'label' => 'Authenticated'])->save();
		Role::create(['id' => 'editor', 'label' => 'Editor'])->save();
		$this->doRequest(Request::create('/legacy-d9/roles'));
		self::assertStringContainsString('authenticated', $this->getRawContent());
		self::assertStringContainsString('editor', $this->getRawContent());
		self::assertStringNotContainsString('anonymous', $this->getRawContent());
	}

	public function testDebugPrintsTheDataThroughTheCronHook(): void
	{
		$this->enableLegacy('legacy_d9_basic');
		$messages = [];
		set_error_handler(static function (int $level, string $message) use (&$messages): bool {
			$messages[] = [$level, $message];
			return true;
		});
		try {
			\Drupal::moduleHandler()->invoke('legacy_d9_basic', 'cron');
			debug('x', null, false);
		} finally {
			restore_error_handler();
		}
		self::assertSame(
			[
				E_USER_NOTICE,
				"legacy_d9_basic: <pre>Array\n(\n    [cron] =&gt; legacy d9 basic\n)\n</pre>",
			],
			$messages[0],
		);
		self::assertSame([E_USER_NOTICE, '<pre>&#039;x&#039;</pre>'], $messages[1]);
	}
}
