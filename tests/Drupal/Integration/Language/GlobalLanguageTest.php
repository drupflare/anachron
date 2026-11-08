<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Integration\Language;

use Drupflare\Anachron\Tests\Drupal\Utils\RequestTrait;
use Drupflare\Anachron\Tests\Drupal\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;

final class GlobalLanguageTest extends IntegrationTestCase
{
	use RequestTrait;

	public function testGlobalLanguage(): void
	{
		$this->doRequest(Request::create('/'));
		$this->assertArrayHasKey('language', $GLOBALS);
		self::assertEquals('en', $GLOBALS['language']->language);
		$this->assertArrayHasKey('language_content', $GLOBALS);
		self::assertEquals('en', $GLOBALS['language_content']->language);
		$this->assertArrayHasKey('language_url', $GLOBALS);
		self::assertEquals('en', $GLOBALS['language_url']->language);
	}
}
