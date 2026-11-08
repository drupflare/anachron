<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Legacy;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupflare\Anachron\Drupal\Extension\LegacyInfoParser;
use Drupflare\Anachron\Tests\Drupal\Utils\RequestTrait;
use Drupflare\Anachron\Tests\Legacy\Support\FixtureModuleList;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ChildDefinition;

/**
 * Runs code written for an older Drupal through real discovery and a real module install.
 *
 * Fixtures live in tests/fixtures/legacy/<era>/<module>/ and declare the core they were written
 * for. Enabling one without the switch must be refused; enabling it with the switch is what a
 * legacy site does. A fixture API anachron does not serve yet is recorded with gap(), which shows
 * as a skipped test with the reason, so a fix turns the skip into a pass.
 */
abstract class LegacyTestCase extends KernelTestBase
{
	use RequestTrait;

	protected static $modules = ['system', 'user'];

	protected function setUp(): void
	{
		$this->strictConfigSchema = false;
		parent::setUp();
	}

	public function register(ContainerBuilder $container): void
	{
		parent::register($container);
		FixtureModuleList::$eras = [static::era()];
		$container->setDefinition(
			FixtureModuleList::class,
			(new ChildDefinition('extension.list.module'))->setDecoratedService(
				'extension.list.module',
			),
		);
	}

	/**
	 * The last namespace segment, so Legacy\D7 reads fixtures/legacy/d7 .*/
	protected static function era(): string
	{
		$namespace = (new ReflectionClass(static::class))->getNamespaceName();
		return strtolower(substr($namespace, (int) strrpos($namespace, '\\') + 1));
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['conf'][LegacyInfoParser::SWITCH]);
		parent::tearDown();
	}

	protected function allowOlderCore(): void
	{
		$GLOBALS['conf'][LegacyInfoParser::SWITCH] = true;
	}

	protected function enableLegacy(string ...$modules): void
	{
		$this->allowOlderCore();
		$this->container->get('module_installer')->install($modules);
		$this->container = \Drupal::getContainer();
	}

	protected function gap(string $what): never
	{
		self::markTestSkipped('gap: ' . $what);
	}
}
