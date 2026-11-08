<?php

/**
 * @file
 * Runs php -l over every PHP file in the repository.
 *
 * With no arguments it lints every file. With file paths it lints only those, which is how the
 * pre-commit hook calls it.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$skip = ['vendor', 'node_modules', 'drupal-root', '.git'];
$extensions = ['php', 'module', 'install', 'inc', 'theme', 'profile', 'engine'];

$argFiles = array_slice($argv, 1);
if ($argFiles !== []) {
	$files = [];
	foreach ($argFiles as $arg) {
		$path = realpath($arg);
		if ($path === false || !is_file($path)) {
			continue;
		}
		if (in_array(pathinfo($path, PATHINFO_EXTENSION), $extensions, true)) {
			$files[] = $path;
		}
	}
	sort($files);
	if ($files === []) {
		echo "no php files to lint\n";
		exit(0);
	}
} else {
	$files = [];
	$walk = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
			static function (SplFileInfo $file) use ($skip): bool {
				return !($file->isDir() && in_array($file->getFilename(), $skip, true));
			},
		),
	);
	foreach ($walk as $file) {
		if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
			$files[] = $file->getPathname();
		}
	}
	sort($files);
}

if ($files === []) {
	fwrite(STDERR, "No PHP files found under $root.\n");
	exit(2);
}

$failed = 0;
foreach ($files as $file) {
	$output = [];
	$status = 0;
	exec(
		escapeshellarg(PHP_BINARY) . ' -n -l ' . escapeshellarg($file) . ' 2>&1',
		$output,
		$status,
	);
	$relative = substr($file, strlen($root) + 1);
	if ($status === 0) {
		echo "  ok   $relative\n";
	} else {
		$failed++;
		echo "  FAIL $relative -- " . implode(' ', $output) . "\n";
	}
}

printf("\n%d file%s linted, %d failed\n", count($files), count($files) === 1 ? '' : 's', $failed);
exit($failed === 0 ? 0 : 1);
