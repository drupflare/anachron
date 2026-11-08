<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Extension;

use PHPUnit\Framework\TestCase;

final class InfoParserAliasTest extends TestCase
{
	public function testSwitchOnMakesCoreInfoParserTheLegacyOne(): void
	{
		self::assertSame('legacy', $this->resolvedParser(true));
	}

	public function testSwitchOffLeavesCoreInfoParserAlone(): void
	{
		self::assertSame('core', $this->resolvedParser(false));
	}

	private function resolvedParser(bool $switch): string
	{
		$root = dirname(__DIR__, 4);
		$code =
			($switch ? '$GLOBALS["conf"]["anachron_ignore_core_requirement"] = true;' : '') .
			'require ' .
			var_export($root . '/vendor/autoload.php', true) .
			';' .
			'echo is_a("Drupal\\\\Core\\\\Extension\\\\InfoParser", "Drupflare\\\\Anachron\\\\Drupal\\\\Extension\\\\LegacyInfoParser", true) ? "legacy" : "core";';
		$output = [];
		exec(
			escapeshellarg(PHP_BINARY) .
				' -d error_reporting=0 -r ' .
				escapeshellarg($code) .
				' 2>&1',
			$output,
		);
		return trim(implode('', $output));
	}
}
