<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Unit\Php;

use PHPUnit\Framework\TestCase;

final class PolyfillsTest extends TestCase
{
	public function testEachWalksAnArrayAndAdvancesThePointer(): void
	{
		$data = ['a' => 1, 'b' => 2];
		$seen = [];
		while (($pair = each($data)) !== false) {
			$seen[] = [$pair['key'], $pair['value'], $pair[0], $pair[1]];
		}
		$this->assertSame([['a', 1, 'a', 1], ['b', 2, 'b', 2]], $seen);
	}

	public function testEachReturnsFalseOnAnEmptyArray(): void
	{
		$data = [];
		$this->assertFalse(each($data));
	}

	public function testEachKeepsFalsyValuesAndKeyZero(): void
	{
		$data = [0 => 0];
		$pair = each($data);
		$this->assertSame([1 => 0, 'value' => 0, 0 => 0, 'key' => 0], $pair);
		$this->assertFalse(each($data));
	}

	public function testMagicQuotesReadAsDisabled(): void
	{
		$this->assertFalse(get_magic_quotes_gpc());
		$this->assertFalse(get_magic_quotes_runtime());
	}

	public function testSettingRuntimeMagicQuotesSucceedsOnlyWhenDisabling(): void
	{
		$this->assertTrue(set_magic_quotes_runtime(false));
		$this->assertTrue(set_magic_quotes_runtime(0));
		$this->assertFalse(set_magic_quotes_runtime(true));
		$this->assertFalse(set_magic_quotes_runtime(1));
	}
}
