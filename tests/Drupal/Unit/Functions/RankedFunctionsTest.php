<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Functions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RankedFunctionsTest extends TestCase
{
	/**
	 * @return array<string, array{mixed, int}>
	 */
	public static function strlenProvider(): array
	{
		return [
			'empty' => ['', 0],
			'null' => [null, 0],
			'ascii' => ['abc', 3],
			'multibyte' => ["t\u{e9}st\u{4e2d}", 5],
		];
	}

	#[DataProvider('strlenProvider')]
	public function testDrupalStrlen(mixed $text, int $expected): void
	{
		self::assertSame($expected, drupal_strlen($text));
	}

	public function testCaseFunctions(): void
	{
		self::assertSame("\u{e9}cole", drupal_strtolower("\u{c9}COLE"));
		self::assertSame('', drupal_strtolower(null));
		self::assertSame("\u{c9}COLE", drupal_strtoupper("\u{e9}cole"));
		self::assertSame("\u{c9}cole", drupal_ucfirst("\u{e9}cole"));
		self::assertSame('Abc', drupal_ucfirst('abc'));
		self::assertSame('', drupal_ucfirst(''));
	}

	/**
	 * @return array<string, array{mixed, int, int|null, string}>
	 */
	public static function substrProvider(): array
	{
		return [
			'from start' => ['abcdef', 2, null, 'cdef'],
			'length' => ['abcdef', 1, 3, 'bcd'],
			'negative start' => ['abcdef', -2, null, 'ef'],
			'multibyte' => ["\u{4e2d}\u{6587}abc", 1, 2, "\u{6587}a"],
			'null text' => [null, 0, 2, ''],
			'past end' => ['ab', 5, null, ''],
		];
	}

	#[DataProvider('substrProvider')]
	public function testDrupalSubstr(mixed $text, int $start, ?int $length, string $expected): void
	{
		self::assertSame($expected, drupal_substr($text, $start, $length));
	}

	public function testDecodeEntities(): void
	{
		self::assertSame(
			'<a href="x">\'&',
			decode_entities('&lt;a href=&quot;x&quot;&gt;&#039;&amp;'),
		);
		self::assertSame('&lt;', decode_entities('&amp;lt;'));
		self::assertSame('', decode_entities(null));
	}

	/**
	 * @return array<string, array{mixed, float}>
	 */
	public static function parseSizeProvider(): array
	{
		return [
			'bytes' => ['2', 2.0],
			'k' => ['3K', 3072.0],
			'mb' => ['5MB', 5242880.0],
			'g' => ['10G', 10737418240.0],
			'gib' => ['6GiB', 6442450944.0],
			'bytes word' => ['8 bytes', 8.0],
			'mbytes' => ['9mbytes', 9437184.0],
			'fraction' => ['1.5K', 1536.0],
			'empty' => ['', 0.0],
			'int input' => [512, 512.0],
		];
	}

	#[DataProvider('parseSizeProvider')]
	public function testParseSize(mixed $size, float $expected): void
	{
		self::assertSame($expected, parse_size($size));
	}

	public function testDrupalIsCli(): void
	{
		self::assertTrue(drupal_is_cli());
		$_SERVER['SERVER_SOFTWARE'] = 'Apache';
		try {
			self::assertFalse(drupal_is_cli());
		} finally {
			unset($_SERVER['SERVER_SOFTWARE']);
		}
	}

	/**
	 * @return array<string, array{array<mixed>, string}>
	 */
	public static function buildQueryProvider(): array
	{
		return [
			'empty' => [[], ''],
			'simple' => [['a' => '1', 'b' => 'x y'], 'a=1&b=x%20y'],
			'slash decoded' => [['p' => 'a/b'], 'p=a/b'],
			'null value' => [['flag' => null], 'flag'],
			'nested' => [['a' => ['b' => 'c', 'd' => ['e' => 1]]], 'a%5Bb%5D=c&a%5Bd%5D%5Be%5D=1'],
		];
	}

	/**
	 * @param array<mixed> $query
	 */
	#[DataProvider('buildQueryProvider')]
	public function testDrupalHttpBuildQuery(array $query, string $expected): void
	{
		self::assertSame($expected, drupal_http_build_query($query));
	}

	public function testDrupalGetQueryParameters(): void
	{
		$query = ['q' => 'node/1', 'page' => '2', 'f' => ['a' => '1', 'b' => '2']];
		self::assertSame(
			['page' => '2', 'f' => ['a' => '1', 'b' => '2']],
			drupal_get_query_parameters($query),
		);
		self::assertSame(['page' => '2'], drupal_get_query_parameters($query, ['q', 'f']));
		self::assertSame(
			['page' => '2', 'f' => ['a' => '1']],
			drupal_get_query_parameters($query, ['q', 'f[b]']),
		);
		self::assertSame($query, drupal_get_query_parameters($query, []));
	}
}
