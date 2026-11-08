<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Reset;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupflare\Anachron\Drupal\Language\GlobalLanguageSetter;
use Drupflare\Anachron\Drupal\User\GlobalUserSetter;
use PHPUnit\Framework\TestCase;

final class LegacyGlobalsResetTest extends TestCase
{
	protected function tearDown(): void
	{
		unset(
			$GLOBALS['user'],
			$GLOBALS['language'],
			$GLOBALS['language_content'],
			$GLOBALS['language_url'],
		);
	}

	public function testUserResetDropsTheGlobalAndSetsNothing(): void
	{
		$GLOBALS['user'] = 'request 1 user';
		(new GlobalUserSetter())->reset();
		self::assertArrayNotHasKey('user', $GLOBALS);
	}

	public function testLanguageResetDropsAllThreeGlobals(): void
	{
		$GLOBALS['language'] = 'fr';
		$GLOBALS['language_content'] = 'fr';
		$GLOBALS['language_url'] = 'fr';
		$setter = new GlobalLanguageSetter(
			$this->createMock(LanguageManagerInterface::class),
			$this->createMock(ModuleHandlerInterface::class),
		);
		$setter->reset();
		self::assertArrayNotHasKey('language', $GLOBALS);
		self::assertArrayNotHasKey('language_content', $GLOBALS);
		self::assertArrayNotHasKey('language_url', $GLOBALS);
	}
}
