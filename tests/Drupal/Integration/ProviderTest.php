<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration;

use Drupal\KernelTests\KernelTestBase;
use Drupflare\Anachron\Drupal\Extension\LegacyInfoParser;
use Drupflare\Anachron\Drupal\Language\GlobalLanguageSetter;
use Drupflare\Anachron\Drupal\Routing\HookMenuRoutes;
use Drupflare\Anachron\Drupal\User\GlobalUserSetter;

final class ProviderTest extends KernelTestBase
{
	public function testRegister(): void
	{
		self::assertTrue($this->container->has(HookMenuRoutes::class));
	}

	public function testLibraryDiscoveryStillAnswersTheCollectorCalls(): void
	{
		$discovery = $this->container->get('library.discovery');
		$discovery->clear();
		$discovery->reset();
		self::assertTrue(method_exists($discovery, 'destruct'));
	}

	public function testInfoParserIsTheLegacyOne(): void
	{
		self::assertInstanceOf(LegacyInfoParser::class, $this->container->get('info_parser'));
	}

	public function testLegacyGlobalCarriersAreTaggedForReset(): void
	{
		$tagged = array_keys($this->container->findTaggedServiceIds('drupflare.reset'));
		self::assertContains(GlobalUserSetter::class, $tagged);
		self::assertContains(GlobalLanguageSetter::class, $tagged);
	}
}
