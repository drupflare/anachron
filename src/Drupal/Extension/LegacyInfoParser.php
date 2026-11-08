<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal\Extension;

use Drupal\Core\Extension\InfoParserDynamic;
use Drupal\Core\Extension\InfoParserException;
use Drupflare\Anachron\Drupal\Switches;

/**
 * Lets an extension that declares an older core run, when the site opts in.
 *
 * Core marks an extension `core_incompatible` when its `core_version_requirement` excludes the
 * running release, and refuses to enable it. With the switch on, the flag is cleared and the
 * declared requirement is kept in `anachron_core_requirement`, so a report can say how many
 * extensions ran past what they declared. Passing the check says nothing about whether the code
 * works; that is what running it finds out.
 */
final class LegacyInfoParser extends InfoParserDynamic
{
	public const SWITCH = 'anachron_ignore_core_requirement';

	private const MISSING = "'core_version_requirement' key must be present";

	/**
	 * Parses an info file, clearing the core incompatibility flag when the site opts in.
	 *
	 * @param string $filename
	 *   The path to the info file.
	 *
	 * @return array<string, mixed>
	 *   The parsed info.
	 */
	public function parse($filename)
	{
		if (!self::enabled()) {
			return parent::parse($filename);
		}

		$declared = null;
		try {
			$info = parent::parse($filename);
			$declared = $info['core_version_requirement'] ?? null;
		} catch (InfoParserException $e) {
			if (!str_contains($e->getMessage(), self::MISSING)) {
				throw $e;
			}
			$info = $this->parseWithRequirement($filename);
			$declared = '(none)';
		}

		if ($declared !== null && ($info['core_incompatible'] || $declared === '(none)')) {
			$info['anachron_core_requirement'] = $declared;
			$info['core_incompatible'] = false;
		}
		return $info;
	}

	/**
	 * Tells whether the site turned the version switch on.
	 */
	private static function enabled(): bool
	{
		return Switches::on(self::SWITCH);
	}

	/**
	 * Parses an info file that lacks a core version requirement.
	 *
	 * @param string $filename
	 *   The path to the info file.
	 *
	 * @return array<string, mixed>
	 *   The parsed info, read from a copy that declares any core version.
	 */
	private function parseWithRequirement(string $filename): array
	{
		$source = file_get_contents($filename);
		$copy = tempnam(sys_get_temp_dir(), 'anachron-info-');
		if ($source === false || $copy === false) {
			throw new InfoParserException("Unable to read $filename");
		}
		try {
			file_put_contents($copy, rtrim($source) . "\ncore_version_requirement: '*'\n");
			return parent::parse($copy);
		} finally {
			unlink($copy);
		}
	}
}
