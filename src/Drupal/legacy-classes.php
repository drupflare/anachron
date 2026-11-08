<?php

declare(strict_types=1);

namespace Drupal\Component\Utility {
	use Drupal\Component\Render\FormattableMarkup;
	use Drupal\Component\Render\HtmlEscapedText;
	use Drupal\Component\Render\MarkupInterface;

	// removed in Drupal 9.0; the three methods Drupal 8.9 still had
	if (!class_exists(SafeMarkup::class)) {
		/**
		 * The three SafeMarkup methods that Drupal 8.9 still had.
		 */
		final class SafeMarkup
		{
			/**
			 * Tells whether a value is marked safe for output.
			 *
			 * @param mixed $string
			 *   The value to test.
			 * @param string $strategy
			 *   Ignored, kept for the Drupal 8 signature.
			 *
			 * @return bool
			 *   True when the value is markup.
			 */
			public static function isSafe($string, $strategy = 'html'): bool
			{
				return $string instanceof MarkupInterface;
			}

			/**
			 * Escapes text for HTML output.
			 *
			 * @param mixed $text
			 *   The text to escape.
			 *
			 * @return \Drupal\Component\Render\HtmlEscapedText
			 *   The escaped text.
			 */
			public static function checkPlain($text): HtmlEscapedText
			{
				return new HtmlEscapedText($text);
			}

			/**
			 * Formats a string with placeholders.
			 *
			 * @param string $string
			 *   The string with placeholders.
			 * @param array $args
			 *   The placeholder replacements.
			 *
			 * @return \Drupal\Component\Render\FormattableMarkup
			 *   The formatted markup.
			 */
			public static function format($string, array $args): FormattableMarkup
			{
				return new FormattableMarkup($string, $args);
			}
		}
	}
}

namespace {
	use Drupflare\Anachron\Drupal\Extension\LegacyInfoParser;

	// the new class is aliased only when the old name is first asked for, so node is not autoloaded
	spl_autoload_register(static function (string $class): void {
		static $moved = ['Drupal\node\NodeForm' => 'Drupal\node\Form\NodeForm'];
		if (isset($moved[$class]) && class_exists($moved[$class])) {
			class_alias($moved[$class], $class);
		}
	});

	// Core's extension discovery builds its own InfoParser before any container exists, so a
	// decorator cannot reach it and it refuses an info file with no core_version_requirement
	// anywhere in the tree. With the switch on, the legacy parser takes that class name first.
	if (
		!empty($GLOBALS['conf'][LegacyInfoParser::SWITCH]) &&
		!class_exists('Drupal\Core\Extension\InfoParser', false)
	) {
		class_alias(LegacyInfoParser::class, 'Drupal\Core\Extension\InfoParser');
	}
}
