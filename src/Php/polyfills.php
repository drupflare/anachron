<?php

/**
 * @file
 * Functions PHP removed, defined again under their original names.
 *
 * Old CMS code still calls these. Each definition is guarded, so a host that already
 * defines the name wins. Nothing here imports a CMS class: this file moves to its own
 * package when a second CMS needs it.
 */

declare(strict_types=1);

if (!function_exists('each')) {
	/**
	 * Returns the current key and value of an array and advances the pointer.
	 *
	 * @param array<mixed> $array
	 *   The array to read, by reference.
	 *
	 * @return array<mixed>|false
	 *   The pair as both [0, 1] and ['key', 'value'], or false past the end.
	 */
	function each(array &$array): array|false
	{
		$key = key($array);
		if ($key === null) {
			return false;
		}
		$value = current($array);
		next($array);
		return [1 => $value, 'value' => $value, 0 => $key, 'key' => $key];
	}
}

if (!function_exists('get_magic_quotes_gpc')) {
	/**
	 * Reports magic quotes for request data, which PHP removed in 8.0.
	 *
	 * @return bool
	 *   Always false.
	 */
	function get_magic_quotes_gpc(): bool
	{
		return false;
	}
}

if (!function_exists('get_magic_quotes_runtime')) {
	/**
	 * Reports magic quotes for runtime data, which PHP removed in 8.0.
	 *
	 * @return bool
	 *   Always false.
	 */
	function get_magic_quotes_runtime(): bool
	{
		return false;
	}
}

if (!function_exists('set_magic_quotes_runtime')) {
	/**
	 * Accepts a request to change runtime magic quotes, which no longer exist.
	 *
	 * @param bool|int $new_setting
	 *   The requested setting.
	 *
	 * @return bool
	 *   True when asked to disable them, false when asked to enable them.
	 */
	function set_magic_quotes_runtime(bool|int $new_setting): bool
	{
		return !$new_setting;
	}
}
