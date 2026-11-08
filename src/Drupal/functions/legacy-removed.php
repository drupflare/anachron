<?php

/**
 * @file
 * Functions that Drupal 8 through 10 removed and that code for those releases still calls.
 */

declare(strict_types=1);

use Drupal\Component\Utility\Html;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Drupal\file\IconMimeTypes;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

if (!function_exists('watchdog_exception')) {
	// Drupal 9.5.11 core/includes/bootstrap.inc:317, Drupal 10.6.21 bootstrap.inc:135

	/**
	 * Logs an exception through the logger service, as Drupal 7's watchdog_exception() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). Severity
	 * defaults to RfcLogLevel::ERROR where Drupal 7 used WATCHDOG_ERROR.
	 *
	 * @param string $type
	 *   The logger channel.
	 * @param \Exception $exception
	 *   The exception to log.
	 * @param string|null $message
	 *   The message, or null for the default exception message.
	 * @param array $variables
	 *   Placeholder replacements. Exception details are merged in.
	 * @param int $severity
	 *   The RFC 5424 log level.
	 * @param string|null $link
	 *   An optional link to attach to the entry.
	 */
	function watchdog_exception(
		$type,
		Exception $exception,
		$message = null,
		$variables = [],
		$severity = RfcLogLevel::ERROR,
		$link = null,
	) {
		if (empty($message)) {
			$message = Error::DEFAULT_ERROR_MESSAGE;
		}

		if ($link) {
			$variables['link'] = $link;
		}

		$variables += Error::decodeException($exception);

		\Drupal::logger($type)->log($severity, $message, $variables);
	}
}

if (!function_exists('module_load_include')) {
	// Drupal 9.5.11 core/includes/module.inc:76, Drupal 10.6.21 module.inc:42

	/**
	 * Includes a file from a module's directory, as Drupal 7's module_load_include() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). Drupal 7
	 * cached each result. This version looks the module path up in the extension.list.module
	 * service.
	 *
	 * @param string $type
	 *   The file extension without the dot, such as "inc".
	 * @param string $module
	 *   The module machine name.
	 * @param string|null $name
	 *   The file base name, or null to use the module name.
	 *
	 * @return string|false
	 *   The included file path, or false when the file does not exist.
	 */
	function module_load_include($type, $module, $name = null)
	{
		if (!isset($name)) {
			$name = $module;
		}

		if (\Drupal::hasService('extension.list.module')) {
			$file =
				\Drupal::root() .
				'/' .
				\Drupal::service('extension.list.module')->getPath($module) .
				"/$name.$type";
			if (is_file($file)) {
				require_once $file;
				return $file;
			}
		}
		return false;
	}
}

if (!function_exists('user_role_names')) {
	// Drupal 10.6.21 user.module:845 with user_roles() (user.module:934) inlined

	/**
	 * Returns role labels keyed by role ID, as Drupal 10's user_role_names() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). Drupal 7
	 * had no function of this name; its user_roles() covered the same job.
	 *
	 * @param bool $members_only
	 *   Whether to leave out the anonymous role.
	 * @param string|null $permission
	 *   When set, only roles holding this permission are returned.
	 *
	 * @return array
	 *   Role labels keyed by role ID.
	 */
	function user_role_names($members_only = false, $permission = null)
	{
		$roles = Role::loadMultiple();
		if ($members_only) {
			unset($roles[RoleInterface::ANONYMOUS_ID]);
		}

		if (!empty($permission)) {
			$roles = array_filter($roles, fn($role) => $role->hasPermission($permission));
		}

		return array_map(fn($item) => $item->label(), $roles);
	}
}

if (!function_exists('file_icon_class')) {
	// Drupal 10.6.21 file.module:1298 delegates to the same class

	/**
	 * Returns the icon class for a MIME type, as Drupal 10's file_icon_class() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). Drupal 7
	 * had no function of this name.
	 *
	 * @param string $mime_type
	 *   The MIME type.
	 *
	 * @return string
	 *   The icon class.
	 */
	function file_icon_class($mime_type)
	{
		return IconMimeTypes::getIconClass($mime_type);
	}
}

if (!function_exists('user_password')) {
	// Drupal 9.5.11 user.module:235

	/**
	 * Generates a random password, as Drupal 7's user_password() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). The
	 * password comes from the password_generator service.
	 *
	 * @param int $length
	 *   The password length.
	 *
	 * @return string
	 *   The generated password.
	 */
	function user_password($length = 10)
	{
		return \Drupal::service('password_generator')->generate($length);
	}
}

if (!function_exists('debug')) {
	// Drupal 9.5.11 core/includes/common.inc:629

	/**
	 * Prints a value as an escaped, preformatted notice, as Drupal 7's debug() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). $print_r
	 * defaults to true here, where Drupal 7 defaulted to false.
	 *
	 * @param mixed $data
	 *   The value to print.
	 * @param string|null $label
	 *   An optional label.
	 * @param bool $print_r
	 *   True for print_r() output, false for var_export().
	 */
	function debug($data, $label = null, $print_r = true)
	{
		$string = Html::escape($print_r ? print_r($data, true) : var_export($data, true));
		$string = '<pre>' . $string . '</pre>';

		trigger_error(trim($label ? "$label: $string" : $string));
	}
}

if (!function_exists('file_save_data')) {
	// Drupal 9.5.11 file.module:519; the integer flags map to FileExists as in 11.4.11

	/**
	 * Saves a string to a new file entity, as Drupal 7's file_save_data() did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). It calls
	 * the file.repository service and maps the old integer replace flags to FileExists. It
	 * returns false on a file or stream wrapper error.
	 *
	 * @param string $data
	 *   The contents to write.
	 * @param string|null $destination
	 *   The destination URI, or null for the default scheme root.
	 * @param int|\Drupal\Core\File\FileExists $replace
	 *   The behavior when the file exists: 0 renames, 2 errors, anything else replaces.
	 *
	 * @return \Drupal\file\FileInterface|false
	 *   The file entity, or false on failure.
	 */
	function file_save_data(
		$data,
		$destination = null,
		$replace = FileSystemInterface::EXISTS_RENAME,
	) {
		if (empty($destination)) {
			$destination = \Drupal::config('system.file')->get('default_scheme') . '://';
		}
		if (!($replace instanceof FileExists)) {
			$replace = match ((int) $replace) {
				0 => FileExists::Rename,
				2 => FileExists::Error,
				default => FileExists::Replace,
			};
		}
		try {
			return \Drupal::service('file.repository')->writeData($data, $destination, $replace);
		} catch (InvalidStreamWrapperException $e) {
			\Drupal::logger('file')->notice(
				'The data could not be saved because the destination %destination is invalid. This may be caused by improper use of file_save_data() or a missing stream wrapper.',
				['%destination' => $destination],
			);
			\Drupal::messenger()->addError(
				t(
					'The data could not be saved because the destination is invalid. More information is available in the system log.',
				),
			);
			return false;
		} catch (FileException $e) {
			return false;
		}
	}
}

if (!function_exists('file_munge_filename')) {
	// Drupal 9.5.11 core/includes/file.inc:158

	/**
	 * Renames a file so a double extension cannot run as code, as Drupal 7's did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). The
	 * allow_insecure_uploads setting comes from system.file config, where Drupal 7 used a
	 * variable.
	 *
	 * @param string $filename
	 *   The file name.
	 * @param string $extensions
	 *   The space-separated list of allowed extensions.
	 * @param bool $alerts
	 *   Whether to show a status message when the name changes.
	 *
	 * @return string
	 *   The munged file name.
	 */
	function file_munge_filename($filename, $extensions, $alerts = true)
	{
		$original = $filename;

		if (!\Drupal::config('system.file')->get('allow_insecure_uploads')) {
			$filename = str_replace(chr(0), '', $filename);

			$allowed_extensions = array_unique(explode(' ', strtolower(trim($extensions))));
			$allowed_extensions = array_diff(
				$allowed_extensions,
				FileSystemInterface::INSECURE_EXTENSIONS,
			);

			$filename_parts = explode('.', $filename);
			$new_filename = array_shift($filename_parts);
			$final_extension = array_pop($filename_parts);

			foreach ($filename_parts as $filename_part) {
				$new_filename .= '.' . $filename_part;
				if (
					!in_array(strtolower($filename_part), $allowed_extensions) &&
					preg_match('/^[a-zA-Z]{2,5}\d?$/', $filename_part)
				) {
					$new_filename .= '_';
				}
			}
			$filename = $new_filename . '.' . $final_extension;

			if ($alerts && $original != $filename) {
				\Drupal::messenger()->addStatus(
					t('For security reasons, your upload has been renamed to %filename.', [
						'%filename' => $filename,
					]),
				);
			}
		}

		return $filename;
	}
}

if (!function_exists('drupal_get_module_schema')) {
	// Drupal 9.5.11 core/includes/schema.inc:166

	/**
	 * Returns a module's hook_schema() definition, as Drupal 9's did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). Drupal 7
	 * had no function of this name.
	 *
	 * @param string $module
	 *   The module machine name.
	 * @param string|null $table
	 *   A table name, or null for the whole schema.
	 *
	 * @return array
	 *   The table definition or the full schema, empty when none exists.
	 */
	function drupal_get_module_schema($module, $table = null)
	{
		\Drupal::moduleHandler()->loadInclude($module, 'install');
		$schema = \Drupal::moduleHandler()->invoke($module, 'schema');

		if (isset($table)) {
			if (isset($schema[$table])) {
				return $schema[$table];
			}
			return [];
		} elseif (!empty($schema)) {
			return $schema;
		}
		return [];
	}
}

if (!function_exists('drupal_get_installed_schema_version')) {
	// Drupal 9.5.11 core/includes/schema.inc:70

	/**
	 * Returns the installed schema version of a module, as Drupal 7's did.
	 *
	 * Replaces a function absent from Drupal 11.4.11 core (original cited above). It reads
	 * the update.update_hook_registry service, so the Drupal 7 $reset flag has no effect.
	 *
	 * @param string $module
	 *   The module machine name.
	 * @param bool $reset
	 *   Ignored.
	 * @param bool $array
	 *   True to return the versions of all modules keyed by name.
	 *
	 * @return int|array
	 *   The schema version, or all versions when $array is set.
	 */
	function drupal_get_installed_schema_version($module, $reset = false, $array = false)
	{
		$service = \Drupal::service('update.update_hook_registry');
		if ($array) {
			return $service->getAllInstalledVersions();
		}
		return $service->getInstalledVersion((string) $module);
	}
}
