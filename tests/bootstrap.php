<?php

/**
 * @file
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$loader = require $root . '/vendor/autoload.php';

$core = $root . '/vendor/drupal/core';
foreach (
	['BuildTests', 'Tests', 'TestSite', 'KernelTests', 'FunctionalTests', 'TestTools']
	as $namespace
) {
	$loader->add('Drupal\\' . $namespace, $core . '/tests');
}
foreach (['block', 'field', 'node', 'sqlite', 'taxonomy', 'user'] as $module) {
	$loader->addPsr4('Drupal\\' . $module . '\\', $core . '/modules/' . $module . '/src');
}
$loader->addPsr4('Drupal\\entity_test\\', $core . '/modules/system/tests/modules/entity_test/src');
$loader->addPsr4('Drupal\\Tests\\user\\Traits\\', $core . '/modules/user/tests/src/Traits');

file_put_contents(
	$root . '/vendor/drupal/autoload.php',
	"<?php\n\nreturn require __DIR__ . '/../autoload.php';\n",
);
file_put_contents(
	$root . '/vendor/drupal/composer.json',
	"{\n\t\"config\": {\n\t\t\"vendor-dir\": \"../\"\n\t}\n}\n",
);

chdir($root . '/vendor/drupal');
