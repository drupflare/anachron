<?php

declare(strict_types=1);

namespace Drupflare\Anachron\Drupal;

use BadMethodCallException;
use Drupal\Core\Site\Settings;

/**
 * The opt-in switches a legacy site turns on.
 *
 * Every one is off unless the site sets it, either in `$settings` or in `$GLOBALS['conf']`, which a
 * library can set before settings load.
 */
final class Switches
{
	public const IGNORE_CORE_REQUIREMENT = 'anachron_ignore_core_requirement';

	public const LEGACY_FIELD_ACCESS = 'anachron_legacy_field_access';

	/**
	 * Tells whether a switch is on.
	 *
	 * @param string $name
	 *   The switch key.
	 *
	 * @return bool
	 *   True when the key is set to a truthy value.
	 */
	public static function on(string $name): bool
	{
		if (!empty($GLOBALS['conf'][$name])) {
			return true;
		}
		try {
			return (bool) Settings::get($name, false);
		} catch (BadMethodCallException) {
			return false;
		}
	}
}
