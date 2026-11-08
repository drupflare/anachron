<?php

/**
 * @file
 * Drupal 7 functions that old code calls often and Drupal 11 no longer defines.
 */

declare(strict_types=1);

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

if (!function_exists('drupal_strlen')) {
	/**
	 * Returns the number of characters in a string, as Drupal 7's drupal_strlen() did.
	 *
	 * Always uses mb_strlen(). Drupal 7's byte-counting fallback for sites without mbstring
	 * is gone.
	 *
	 * @param mixed $text
	 *   The string to measure. Null counts as zero characters.
	 *
	 * @return int
	 *   The length of the string in characters.
	 */
	function drupal_strlen($text)
	{
		return $text === null ? 0 : mb_strlen((string) $text);
	}
}

if (!function_exists('drupal_strtolower')) {
	/**
	 * Lowercases a UTF-8 string, as Drupal 7's drupal_strtolower() did with mbstring.
	 *
	 * @param mixed $text
	 *   The string to convert.
	 *
	 * @return string
	 *   The lowercased string.
	 */
	function drupal_strtolower($text)
	{
		return mb_strtolower((string) $text);
	}
}

if (!function_exists('drupal_strtoupper')) {
	/**
	 * Uppercases a UTF-8 string, as Drupal 7's drupal_strtoupper() did with mbstring.
	 *
	 * @param mixed $text
	 *   The string to convert.
	 *
	 * @return string
	 *   The uppercased string.
	 */
	function drupal_strtoupper($text)
	{
		return mb_strtoupper((string) $text);
	}
}

if (!function_exists('drupal_substr')) {
	/**
	 * Cuts a piece out of a string by character index, as Drupal 7's drupal_substr() did.
	 *
	 * @param mixed $text
	 *   The input string.
	 * @param int $start
	 *   The character position at which to start reading.
	 * @param int|null $length
	 *   The number of characters to read, or null to read to the end.
	 *
	 * @return string
	 *   The shortened string.
	 */
	function drupal_substr($text, $start, $length = null)
	{
		$text = (string) $text;
		return $length === null ? mb_substr($text, $start) : mb_substr($text, $start, $length);
	}
}

if (!function_exists('drupal_ucfirst')) {
	/**
	 * Uppercases the first character of a string, as Drupal 7's drupal_ucfirst() did.
	 *
	 * @param mixed $text
	 *   The string to convert.
	 *
	 * @return string
	 *   The string with its first character uppercased.
	 */
	function drupal_ucfirst($text)
	{
		return drupal_strtoupper(drupal_substr($text, 0, 1)) . drupal_substr($text, 1);
	}
}

if (!function_exists('decode_entities')) {
	/**
	 * Decodes HTML entities to UTF-8 characters, as Drupal 7's decode_entities() did.
	 *
	 * @param mixed $text
	 *   The text to decode.
	 *
	 * @return string
	 *   The decoded text, with single and double quote entities decoded too.
	 */
	function decode_entities($text)
	{
		return html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('parse_size')) {
	/**
	 * Converts a size string such as "2M" to bytes, as Drupal 7's parse_size() did.
	 *
	 * @param mixed $size
	 *   The size string. A unit letter from "bkmgtpezy" scales the number by powers of 1024.
	 *
	 * @return float
	 *   The size in bytes, rounded to a whole number.
	 */
	function parse_size($size)
	{
		$size = (string) $size;
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
		$size = (float) preg_replace('/[^0-9\.]/', '', $size);
		if ($unit) {
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		return round($size);
	}
}

if (!function_exists('drupal_is_cli')) {
	/**
	 * Tells whether the code runs from the command line, as Drupal 7's drupal_is_cli() did.
	 *
	 * Reads a missing argc as not numeric instead of raising a notice.
	 *
	 * @return bool
	 *   True when running under the CLI.
	 */
	function drupal_is_cli()
	{
		return !isset($_SERVER['SERVER_SOFTWARE']) &&
			(php_sapi_name() === 'cli' ||
				(is_numeric($_SERVER['argc'] ?? null) && $_SERVER['argc'] > 0));
	}
}

if (!function_exists('drupal_http_build_query')) {
	/**
	 * Builds a URL-encoded query string from an array, as Drupal 7's did.
	 *
	 * Keys without a value are emitted bare, and slashes in values stay unencoded.
	 *
	 * @param array $query
	 *   The query parameters, which may be nested.
	 * @param string $parent
	 *   The encoded parent key for nested values. Internal use.
	 *
	 * @return string
	 *   The query string, without a leading question mark.
	 */
	function drupal_http_build_query(array $query, $parent = '')
	{
		$params = [];

		foreach ($query as $key => $value) {
			$key = $parent ? $parent . rawurlencode('[' . $key . ']') : rawurlencode((string) $key);

			if (is_array($value)) {
				$params[] = drupal_http_build_query($value, $key);
			} elseif (!isset($value)) {
				$params[] = $key;
			} else {
				$params[] = $key . '=' . str_replace('%2F', '/', rawurlencode((string) $value));
			}
		}

		return implode('&', $params);
	}
}

if (!function_exists('drupal_get_query_parameters')) {
	/**
	 * Returns the query parameters minus the excluded keys, as Drupal 7's did.
	 *
	 * The default source is the current Symfony request instead of Drupal 7's $_GET.
	 *
	 * @param array|null $query
	 *   The parameters to filter, or null for the current request's query.
	 * @param array $exclude
	 *   The keys to drop. Nested keys use the "a[b]" form.
	 * @param string $parent
	 *   The parent key for nested values. Internal use.
	 *
	 * @return array
	 *   The filtered parameters.
	 */
	function drupal_get_query_parameters(?array $query = null, array $exclude = ['q'], $parent = '')
	{
		if (!isset($query)) {
			$query = \Drupal::request()->query->all();
		}
		if (empty($exclude)) {
			return $query;
		} elseif (!$parent) {
			$exclude = array_flip($exclude);
		}

		$params = [];
		foreach ($query as $key => $value) {
			$string_key = $parent ? $parent . '[' . $key . ']' : $key;
			if (isset($exclude[$string_key])) {
				continue;
			}

			if (is_array($value)) {
				$params[$key] = drupal_get_query_parameters($value, $exclude, (string) $string_key);
			} else {
				$params[$key] = $value;
			}
		}

		return $params;
	}
}

if (!function_exists('format_plural')) {
	/**
	 * Formats a string containing a count, as Drupal 7's format_plural() did.
	 *
	 * Delegates to the translation service's formatPlural().
	 *
	 * @param int|float|string $count
	 *   The item count.
	 * @param string $singular
	 *   The text for a count of 1.
	 * @param string $plural
	 *   The text for any other count, with "@count" as the placeholder.
	 * @param array $args
	 *   Placeholder replacements.
	 * @param array $options
	 *   Translation options, such as "langcode".
	 *
	 * @return string
	 *   The translated string.
	 */
	function format_plural($count, $singular, $plural, array $args = [], array $options = [])
	{
		return (string) \Drupal::translation()->formatPlural(
			$count,
			$singular,
			$plural,
			$args,
			$options,
		);
	}
}

if (!function_exists('format_size')) {
	/**
	 * Formats a byte count as a human-readable string, as Drupal 7's format_size() did.
	 *
	 * @param int|float $size
	 *   The size in bytes.
	 * @param string|null $langcode
	 *   The language code for the units, or null for the current language.
	 *
	 * @return string
	 *   The size with its unit, such as "1.5 MB".
	 */
	function format_size($size, $langcode = null)
	{
		$options = ['langcode' => $langcode];
		if ($size < 1024) {
			return format_plural($size, '1 byte', '@count bytes', [], $options);
		}

		$size = $size / 1024;
		$translation = \Drupal::translation();
		$units = [
			$translation->translate('@size KB', [], $options),
			$translation->translate('@size MB', [], $options),
			$translation->translate('@size GB', [], $options),
			$translation->translate('@size TB', [], $options),
			$translation->translate('@size PB', [], $options),
			$translation->translate('@size EB', [], $options),
			$translation->translate('@size ZB', [], $options),
			$translation->translate('@size YB', [], $options),
		];
		foreach ($units as $unit) {
			if (round($size, 2) >= 1024) {
				$size = $size / 1024;
			} else {
				break;
			}
		}
		return str_replace('@size', (string) round($size, 2), (string) $unit);
	}
}

if (!function_exists('format_interval')) {
	/**
	 * Formats a time interval with the requested granularity, as Drupal 7's did.
	 *
	 * @param int $interval
	 *   The length of the interval in seconds.
	 * @param int $granularity
	 *   The maximum number of units to show.
	 * @param string|null $langcode
	 *   The language code for the units, or null for the current language.
	 *
	 * @return string
	 *   The interval, such as "1 week 2 days", or "0 sec" for an empty interval.
	 */
	function format_interval($interval, $granularity = 2, $langcode = null)
	{
		$units = [
			'1 year|@count years' => 31536000,
			'1 month|@count months' => 2592000,
			'1 week|@count weeks' => 604800,
			'1 day|@count days' => 86400,
			'1 hour|@count hours' => 3600,
			'1 min|@count min' => 60,
			'1 sec|@count sec' => 1,
		];
		$options = ['langcode' => $langcode];
		$output = '';
		foreach ($units as $key => $value) {
			$key = explode('|', $key);
			if ($interval >= $value) {
				$output .=
					($output ? ' ' : '') .
					format_plural(floor($interval / $value), $key[0], $key[1], [], $options);
				$interval = (int) $interval % $value;
				$granularity--;
			}

			if ($granularity == 0) {
				break;
			}
		}
		return $output ?: (string) \Drupal::translation()->translate('0 sec', [], $options);
	}
}

if (!function_exists('format_date')) {
	/**
	 * Formats a timestamp with the date formatter service, as Drupal 7's format_date() did.
	 *
	 * @param int|string $timestamp
	 *   The Unix timestamp.
	 * @param string $type
	 *   The date format type, such as "short", "medium", "long" or "custom".
	 * @param string $format
	 *   The PHP date format, used when the type is "custom".
	 * @param string|null $timezone
	 *   The timezone name, or null for the user's timezone.
	 * @param string|null $langcode
	 *   The language code, or null for the current language.
	 *
	 * @return string
	 *   The formatted date.
	 */
	function format_date(
		$timestamp,
		$type = 'medium',
		$format = '',
		$timezone = null,
		$langcode = null,
	) {
		return (string) \Drupal::service('date.formatter')->format(
			(int) $timestamp,
			$type,
			$format,
			$timezone,
			$langcode,
		);
	}
}

if (!function_exists('arg')) {
	/**
	 * Returns a component of the current path, as Drupal 7's arg() did.
	 *
	 * Reads the path from the path.current service instead of Drupal 7's $_GET['q'].
	 *
	 * @param int|null $index
	 *   The component to return, or null for all components.
	 * @param string|null $path
	 *   The path to split, or null for the current path.
	 *
	 * @return string|array|null
	 *   The component, the full array when no index is given, or null when it is missing.
	 */
	function arg($index = null, $path = null)
	{
		if (!isset($path)) {
			$path = ltrim(\Drupal::service('path.current')->getPath(), '/');
		}
		$arguments = explode('/', $path);
		if (!isset($index)) {
			return $arguments;
		}
		return $arguments[$index] ?? null;
	}
}

if (!function_exists('drupal_get_destination')) {
	/**
	 * Returns the destination query parameter for the current page, as Drupal 7's did.
	 *
	 * Unlike Drupal 7, the result is not cached for the request.
	 *
	 * @return array
	 *   An array with a single "destination" key.
	 */
	function drupal_get_destination()
	{
		$query = \Drupal::request()->query->all();
		if (isset($query['destination'])) {
			return ['destination' => $query['destination']];
		}

		$path = ltrim(\Drupal::service('path.current')->getPath(), '/');
		$queryString = drupal_http_build_query(drupal_get_query_parameters($query));
		if ($queryString != '') {
			$path .= '?' . $queryString;
		}
		return ['destination' => $path];
	}
}

if (!function_exists('user_load')) {
	/**
	 * Loads a user entity by ID, as Drupal 7's user_load() did.
	 *
	 * @param int|string $uid
	 *   The user ID.
	 * @param bool $reset
	 *   Whether to reset the entity cache first.
	 *
	 * @return \Drupal\user\UserInterface|false
	 *   The user entity, or false when none exists.
	 */
	function user_load($uid, $reset = false)
	{
		$users = entity_load('user', [$uid], [], (bool) $reset);
		return reset($users);
	}
}

if (!function_exists('user_roles')) {
	/**
	 * Returns role labels keyed by role ID, as Drupal 7's user_roles() did.
	 *
	 * Roles are sorted by weight, then label. Role IDs are machine names, where Drupal 7 used
	 * numeric role IDs.
	 *
	 * @param bool $membersonly
	 *   Whether to leave out the anonymous role.
	 * @param string|null $permission
	 *   When set, only roles holding this permission are returned.
	 *
	 * @return string[]
	 *   Role labels keyed by role ID.
	 */
	function user_roles($membersonly = false, $permission = null)
	{
		$roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
		uasort($roles, static function ($a, $b): int {
			return [$a->getWeight(), strtolower((string) $a->label())] <=> [
				$b->getWeight(),
				strtolower((string) $b->label()),
			];
		});

		$result = [];
		foreach ($roles as $role) {
			if (!empty($permission) && !in_array($permission, $role->getPermissions(), true)) {
				continue;
			}
			if ($membersonly && $role->id() === 'anonymous') {
				continue;
			}
			$result[$role->id()] = (string) $role->label();
		}
		return $result;
	}
}

if (!function_exists('node_access')) {
	/**
	 * Checks node access for an operation, as Drupal 7's node_access() did.
	 *
	 * Fails closed: a missing node, an unsupported operation, an unresolvable account or a node
	 * type string with any operation other than "create" returns false. Access is decided by the
	 * node entity access handler, not by Drupal 7's hook_node_access() and node_access table.
	 *
	 * @param string $op
	 *   The operation: "view", "update", "delete" or "create".
	 * @param \Drupal\node\NodeInterface|string|null $node
	 *   The node, or a node type ID for "create".
	 * @param \Drupal\Core\Session\AccountInterface|object|null $account
	 *   The account to check, or null for the current user.
	 *
	 * @return bool
	 *   True when access is granted.
	 */
	function node_access($op, $node, $account = null)
	{
		if (!$node || !in_array($op, ['view', 'update', 'delete', 'create'], true)) {
			return false;
		}

		if ($account === null) {
			$account = \Drupal::currentUser();
		} elseif (
			is_object($account) &&
			!($account instanceof AccountInterface) &&
			isset($account->uid)
		) {
			$account = \Drupal::entityTypeManager()->getStorage('user')->load($account->uid);
		}
		if (!($account instanceof AccountInterface)) {
			return false;
		}

		if ($node instanceof NodeInterface) {
			return $node->access($op, $account);
		}
		if (is_string($node) && $op === 'create') {
			return \Drupal::entityTypeManager()
				->getAccessControlHandler('node')
				->createAccess($node, $account);
		}
		return false;
	}
}

if (!function_exists('entity_label')) {
	/**
	 * Returns the label of an entity, as Drupal 7's entity_label() did.
	 *
	 * @param string $entity_type
	 *   The entity type ID. Unused; the entity supplies its own label.
	 * @param mixed $entity
	 *   The entity.
	 *
	 * @return string|false
	 *   The label, or false when the value is not an entity or has no label.
	 */
	function entity_label($entity_type, $entity)
	{
		if (!($entity instanceof EntityInterface)) {
			return false;
		}
		$label = $entity->label();
		return $label === null ? false : (string) $label;
	}
}

if (!function_exists('taxonomy_term_load')) {
	/**
	 * Loads a taxonomy term by ID, as Drupal 7's taxonomy_term_load() did.
	 *
	 * @param int|string $tid
	 *   The term ID.
	 *
	 * @return \Drupal\taxonomy\TermInterface|false
	 *   The term, or false when the ID is not numeric or no term exists.
	 */
	function taxonomy_term_load($tid)
	{
		if (!is_numeric($tid)) {
			return false;
		}
		$terms = entity_load('taxonomy_term', [$tid]);
		return reset($terms);
	}
}

if (!function_exists('file_load')) {
	/**
	 * Loads a file entity by ID, as Drupal 7's file_load() did.
	 *
	 * @param int|string $fid
	 *   The file ID.
	 *
	 * @return \Drupal\file\FileInterface|false
	 *   The file entity, or false when none exists.
	 */
	function file_load($fid)
	{
		$files = entity_load('file', [$fid]);
		return reset($files);
	}
}

if (!function_exists('file_create_url')) {
	/**
	 * Returns an absolute URL for a file URI, as Drupal 7's file_create_url() did.
	 *
	 * Uses the file_url_generator service, so Drupal 7's hook_file_url_alter() is not called.
	 *
	 * @param mixed $uri
	 *   The URI or path of the file.
	 *
	 * @return string|false
	 *   The absolute URL, or false when the stream wrapper is invalid.
	 */
	function file_create_url($uri)
	{
		try {
			return \Drupal::service('file_url_generator')->generateAbsoluteString((string) $uri);
		} catch (InvalidStreamWrapperException) {
			return false;
		}
	}
}

if (!function_exists('drupal_get_token')) {
	/**
	 * Returns a CSRF token for a value, as Drupal 7's drupal_get_token() did.
	 *
	 * Delegates to the csrf_token service.
	 *
	 * @param string $value
	 *   An additional value to base the token on.
	 *
	 * @return string
	 *   The token.
	 */
	function drupal_get_token($value = '')
	{
		return \Drupal::csrfToken()->get($value);
	}
}

if (!function_exists('drupal_valid_token')) {
	/**
	 * Validates a CSRF token, as Drupal 7's drupal_valid_token() did.
	 *
	 * @param string $token
	 *   The token to validate.
	 * @param string $value
	 *   The additional value the token was based on.
	 * @param bool $skip_anonymous
	 *   Whether to accept any token for anonymous users.
	 *
	 * @return bool
	 *   True for a valid token, or always true for anonymous users when $skip_anonymous is set.
	 */
	function drupal_valid_token($token, $value = '', $skip_anonymous = false)
	{
		return ($skip_anonymous && \Drupal::currentUser()->isAnonymous()) ||
			\Drupal::csrfToken()->validate($token, $value);
	}
}
