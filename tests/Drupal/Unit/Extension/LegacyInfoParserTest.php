<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Tests\Drupal\Unit\Extension;

use Drupal\Core\Extension\InfoParserException;
use Drupflare\Anachron\Drupal\Extension\LegacyInfoParser;
use PHPUnit\Framework\TestCase;

final class LegacyInfoParserTest extends TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . '/anachron-info-test-' . bin2hex(random_bytes(4));
		mkdir($this->dir);
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['conf'][LegacyInfoParser::SWITCH]);
		foreach (glob($this->dir . '/*') ?: [] as $file) {
			unlink($file);
		}
		rmdir($this->dir);
	}

	public function testDefaultStillRefusesAnOlderCore(): void
	{
		$info = $this->parser()->parse(
			$this->write("name: Old\ntype: module\ncore_version_requirement: ^10\n"),
		);
		self::assertTrue($info['core_incompatible']);
		self::assertArrayNotHasKey('anachron_core_requirement', $info);
	}

	public function testSwitchClearsTheFlagAndKeepsTheDeclaredRequirement(): void
	{
		$this->enable();
		$info = $this->parser()->parse(
			$this->write("name: Old\ntype: module\ncore_version_requirement: ^10\n"),
		);
		self::assertFalse($info['core_incompatible']);
		self::assertSame('^10', $info['anachron_core_requirement']);
	}

	public function testSwitchLeavesACompatibleExtensionAlone(): void
	{
		$this->enable();
		$info = $this->parser()->parse(
			$this->write("name: Fine\ntype: module\ncore_version_requirement: ^10 || ^11 || ^12\n"),
		);
		self::assertFalse($info['core_incompatible']);
		self::assertArrayNotHasKey('anachron_core_requirement', $info);
	}

	public function testDefaultStillRejectsAFileWithNoRequirement(): void
	{
		$this->expectException(InfoParserException::class);
		$this->parser()->parse($this->write("name: Old\ntype: module\ncore: 8.x\n"));
	}

	public function testSwitchParsesAFileWithNoRequirementAndSaysSo(): void
	{
		$this->enable();
		$info = $this->parser()->parse($this->write("name: Old\ntype: module\ncore: 8.x\n"));
		self::assertFalse($info['core_incompatible']);
		self::assertSame('(none)', $info['anachron_core_requirement']);
		self::assertSame('Old', $info['name']);
	}

	public function testSwitchDoesNotHideOtherErrors(): void
	{
		$this->enable();
		$this->expectException(InfoParserException::class);
		$this->parser()->parse($this->write("type: module\ncore_version_requirement: ^10\n"));
	}

	public function testTheSwitchLeavesNoTemporaryFileBehind(): void
	{
		$this->enable();
		$before = glob(sys_get_temp_dir() . '/anachron-info-*') ?: [];
		$this->parser()->parse($this->write("name: Old\ntype: module\ncore: 8.x\n"));
		$after = glob(sys_get_temp_dir() . '/anachron-info-*') ?: [];
		self::assertSame(array_values($before), array_values($after));
	}

	private function parser(): LegacyInfoParser
	{
		return new LegacyInfoParser($this->dir);
	}

	private function enable(): void
	{
		$GLOBALS['conf'][LegacyInfoParser::SWITCH] = true;
	}

	private function write(string $yaml): string
	{
		$path = $this->dir . '/x.info.yml';
		file_put_contents($path, $yaml);
		return $path;
	}
}
