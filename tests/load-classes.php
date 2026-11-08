<?php

/**
 * @file
 * Loads every class under src/ against a real Drupal root.
 *
 * A lint pass cannot see a linkage error: a class that fails to implement an abstract method,
 * or extends a class core removed, is lint-clean and fatals the first time it autoloads.
 * Each class loads in its own process so one fatal does not hide the rest.
 *
 * The first argument, or DRUPAL_ROOT, is the path to a Drupal checkout with vendor/ installed.
 * It is only read.
 */

declare(strict_types=1);

$package = dirname(__DIR__);
$args = array_slice($argv, 1);
$only = null;
if (($i = array_search('--class', $args, true)) !== false) {
	$only = $args[$i + 1] ?? null;
	array_splice($args, $i, 2);
}
$root = $args[0] ?? getenv('DRUPAL_ROOT') ?: null;
if ($root === null || !is_file($root . '/vendor/autoload.php')) {
	fwrite(STDERR, "Pass a Drupal 11.3+ root with vendor/ installed, or set DRUPAL_ROOT.\n");
	exit(2);
}

function bootstrap(string $root, string $package): void
{
	error_reporting(E_ALL);
	ini_set('display_errors', '0');
	$loader = require $root . '/vendor/autoload.php';
	$loader->addPsr4('Drupflare\\Anachron\\', $package . '/src');
	foreach (glob($root . '/core/modules/*', GLOB_ONLYDIR) ?: [] as $dir) {
		$loader->addPsr4('Drupal\\' . basename($dir) . '\\', $dir . '/src');
	}
	foreach (['constants', 'functions'] as $file) {
		$path = $package . '/src/Drupal/' . $file . '.php';
		if (is_file($path)) {
			require_once $path;
		}
	}
	require_once $package . '/src/Php/polyfills.php';
}

if ($only !== null) {
	register_shutdown_function(static function (): void {
		$error = error_get_last();
		if (
			$error !== null &&
			in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR], true)
		) {
			fwrite(STDERR, $error['message'] . "\n");
		}
	});
	bootstrap($root, $package);
	$loaded =
		class_exists($only) || interface_exists($only) || trait_exists($only) || enum_exists($only);
	exit($loaded ? 0 : 1);
}

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
	$class = 'Drupflare\\Anachron\\' . str_replace('/', '\\', $relative);
	if (
		preg_match(
			'#^Drupflare\\\\Anachron\\\\Drupal\\\\(functions|constants|bootstrap|legacy-classes)(\\\\|$)#',
			$class,
		)
	) {
		continue;
	}
	$classes[] = $class;
}
sort($classes);

$pass = 0;
$fail = 0;
foreach ($classes as $class) {
	$output = [];
	$status = 0;
	exec(
		escapeshellarg(PHP_BINARY) .
			' ' .
			escapeshellarg(__FILE__) .
			' ' .
			escapeshellarg($root) .
			' --class ' .
			escapeshellarg($class) .
			' 2>&1',
		$output,
		$status,
	);
	if ($status === 0) {
		$pass++;
		echo "  ok   $class\n";
	} else {
		$fail++;
		$message = trim(implode(' ', $output));
		echo "  FAIL $class" . ($message === '' ? '' : " -- $message") . "\n";
	}
}

foreach (
	['each', 'get_magic_quotes_gpc', 'get_magic_quotes_runtime', 'set_magic_quotes_runtime']
	as $name
) {
	$status = 0;
	$output = [];
	exec(
		escapeshellarg(PHP_BINARY) .
			' -r ' .
			escapeshellarg(
				'$l = require ' .
					var_export($root . '/vendor/autoload.php', true) .
					'; require ' .
					var_export($package . '/src/Php/polyfills.php', true) .
					'; exit(function_exists(' .
					var_export($name, true) .
					') ? 0 : 1);',
			) .
			' 2>&1',
		$output,
		$status,
	);
	if ($status === 0) {
		$pass++;
		echo "  ok   $name()\n";
	} else {
		$fail++;
		echo "  FAIL $name() is not defined after loading against this core\n";
	}
}

printf("\n%d passed, %d failed (%d classes against %s)\n", $pass, $fail, count($classes), $root);
exit($fail === 0 ? 0 : 1);
