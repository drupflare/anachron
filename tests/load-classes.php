<?php

/**
 * @file
 * Loads every class under src/ against a real Drupal root.
 *
 * A lint pass cannot see a linkage error: a class that fails to implement an abstract method,
 * or extends a class core removed, is lint-clean and fatals the first time it autoloads.
 * Loading each class against the real core is the gate that catches it, and it is the check
 * that tells a Drupal 11 core from a Drupal 12 one.
 *
 * The first argument, or DRUPAL_ROOT, is the path to a Drupal checkout with vendor/ installed.
 * It is only read.
 */

declare(strict_types=1);

$package = dirname(__DIR__);
$root = $argv[1] ?? getenv('DRUPAL_ROOT') ?: null;
if ($root === null || !is_file($root . '/vendor/autoload.php')) {
	fwrite(STDERR, "Pass a Drupal 11.3+ root with vendor/ installed, or set DRUPAL_ROOT.\n");
	exit(2);
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

register_shutdown_function(static function (): void {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true)) {
		fwrite(
			STDERR,
			"\nFATAL while loading the package: {$error['message']}\n  at {$error['file']}:{$error['line']}\n",
		);
	}
});

$loader = require $root . '/vendor/autoload.php';
$loader->addPsr4('Drupflare\\Anachron\\', $package . '/src');
require_once $package . '/src/Php/polyfills.php';

$classes = [];
$walk = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($package . '/src', FilesystemIterator::SKIP_DOTS),
);
foreach ($walk as $file) {
	if (!$file->isFile() || $file->getExtension() !== 'php') {
		continue;
	}
	$relative = substr($file->getPathname(), strlen($package . '/src/'), -4);
	if (!str_contains($relative, '/') || basename($relative) === 'polyfills') {
		continue;
	}
	$classes[] = 'Drupflare\\Anachron\\' . str_replace('/', '\\', $relative);
}
sort($classes);

$pass = 0;
$fail = 0;
foreach ($classes as $class) {
	$loaded =
		class_exists($class) ||
		interface_exists($class) ||
		trait_exists($class) ||
		enum_exists($class);
	if ($loaded) {
		$pass++;
		echo "  ok   $class\n";
	} else {
		$fail++;
		echo "  FAIL $class\n";
	}
}

foreach (
	['each', 'get_magic_quotes_gpc', 'get_magic_quotes_runtime', 'set_magic_quotes_runtime']
	as $name
) {
	if (function_exists($name)) {
		$pass++;
		echo "  ok   $name()\n";
	} else {
		$fail++;
		echo "  FAIL $name() is not defined after loading against this core\n";
	}
}

printf("\n%d passed, %d failed (%d classes against %s)\n", $pass, $fail, count($classes), $root);
exit($fail === 0 ? 0 : 1);
