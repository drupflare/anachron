import { expect, test } from 'bun:test';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { importRetrofit } from './import-retrofit';

function write(root: string, files: Record<string, string>): void {
	for (const [path, body] of Object.entries(files)) {
		mkdirSync(join(root, path, '..'), { recursive: true });
		writeFileSync(join(root, path), body);
	}
}

function retrofit(name = 'retrofit-drupal/retrofit'): string {
	const root = mkdtempSync(join(tmpdir(), 'retro-'));
	write(root, {
		'composer.json': JSON.stringify({ name }),
		LICENSE: 'MIT License\n\nCopyright (c) 2023 Matt Glaman\n',
		'bootstrap.php': '<?php\nuse Retrofit\\Drupal\\Provider;\n',
		'src/Provider.php':
			"<?php\nnamespace Retrofit\\Drupal;\n$namespaces['Retrofit\\Drupal'] = __DIR__;\n$x = 'Retrofit\\\\Drupal\\\\Foo';\n",
		'src/functions.php': '<?php\n',
		'src/constants.php': '<?php\n',
		'templates/a.html.twig': 'x',
		'tests/src/FooTest.php': '<?php\nnamespace Retrofit\\Drupal\\Tests\\Unit;\n',
		'tests/data/m/m.info.yml': 'name: m\n',
		'docs/README.md': '# docs\n'
	});
	return root;
}

function target(): string {
	const root = mkdtempSync(join(tmpdir(), 'anachron-'));
	write(root, { 'composer.json': JSON.stringify({ name: 'drupflare/anachron' }) });
	return root;
}

test('files land in the anachron layout with the namespace rewritten', () => {
	const to = target();
	importRetrofit(retrofit(), to);
	const provider = readFileSync(join(to, 'src/Drupal/Provider.php'), 'utf8');
	expect(provider).toContain('namespace Drupflare\\Anachron\\Drupal;');
	expect(provider).toContain("$namespaces['Drupflare\\Anachron\\Drupal']");
	expect(provider).toContain("'Drupflare\\\\Anachron\\\\Drupal\\\\Foo'");
	expect(readFileSync(join(to, 'src/Drupal/bootstrap.php'), 'utf8')).toContain(
		'use Drupflare\\Anachron\\Drupal\\Provider;'
	);
	expect(readFileSync(join(to, 'tests/Drupal/FooTest.php'), 'utf8')).toContain(
		'namespace Drupflare\\Anachron\\Tests\\Drupal\\Unit;'
	);
	expect(existsSync(join(to, 'src/templates/a.html.twig'))).toBe(true);
	expect(existsSync(join(to, 'tests/fixtures/retrofit/m/m.info.yml'))).toBe(true);
	expect(existsSync(join(to, 'docs/retrofit/README.md'))).toBe(true);
});

test('no Retrofit namespace is left behind', () => {
	const to = target();
	importRetrofit(retrofit(), to);
	for (const f of [
		'src/Drupal/Provider.php',
		'src/Drupal/bootstrap.php',
		'tests/Drupal/FooTest.php'
	]) {
		expect(readFileSync(join(to, f), 'utf8')).not.toMatch(/Retrofit\\+Drupal/);
	}
});

test('NOTICE carries the copyright holder and the licence text', () => {
	const to = target();
	importRetrofit(retrofit(), to);
	const notice = readFileSync(join(to, 'NOTICE'), 'utf8');
	expect(notice).toContain('Matt Glaman');
	expect(notice).toContain('MIT License');
});

test('composer autoload gains the legacy function files that exist', () => {
	const to = target();
	importRetrofit(retrofit(), to);
	const composer = JSON.parse(readFileSync(join(to, 'composer.json'), 'utf8'));
	expect(composer.autoload.files).toEqual([
		'src/Drupal/constants.php',
		'src/Drupal/functions.php',
		'src/Drupal/bootstrap.php'
	]);
});

test('a second import and a different source are refused', () => {
	const to = target();
	importRetrofit(retrofit(), to);
	expect(() => importRetrofit(retrofit(), to)).toThrow('already exists');
	expect(() => importRetrofit(retrofit('other/pkg'), target())).toThrow('not retrofit');
});
