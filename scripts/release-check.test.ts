import { expect, test } from 'bun:test';
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { checkRelease } from './release-check';

function repo(over: Record<string, string> = {}): string {
	const root = mkdtempSync(join(tmpdir(), 'release-'));
	const files: Record<string, string> = {
		'package.json': JSON.stringify({ version: '0.1.0' }),
		'composer.json': JSON.stringify({ name: 'drupflare/anachron' }),
		LICENSE: 'MIT License\n\nCopyright (c) 2026 Gregory Mitchell\n',
		...over
	};
	for (const [path, body] of Object.entries(files)) {
		mkdirSync(join(root, path, '..'), { recursive: true });
		writeFileSync(join(root, path), body);
	}
	return root;
}

test('a clean repository has no problems', () => {
	expect(checkRelease(repo(), ['v0.0.9'])).toEqual([]);
});

test('a version that is not semver is reported', () => {
	const root = repo({ 'package.json': JSON.stringify({ version: '1.0' }) });
	expect(checkRelease(root, [])[0]).toContain('not semver');
});

test('an existing tag blocks the release', () => {
	expect(checkRelease(repo(), ['v0.1.0'])).toEqual(['tag v0.1.0 already exists']);
});

test('a version key in composer.json is reported', () => {
	const root = repo({ 'composer.json': JSON.stringify({ version: '0.1.0' }) });
	expect(checkRelease(root, [])[0]).toContain('Packagist reads the tag');
});

test('a missing or wrong LICENSE is reported', () => {
	expect(checkRelease(repo({ LICENSE: 'GPL' }), [])[0]).toContain('LICENSE');
});

test('copied Retrofit files without attribution block the release', () => {
	const root = repo({ 'src/Drupal/Provider.php': '<?php\n' });
	expect(checkRelease(root, [])[0]).toContain('NOTICE');
	const ok = repo({
		'src/Drupal/Provider.php': '<?php\n',
		NOTICE: 'Copyright (c) 2023 Matt Glaman'
	});
	expect(checkRelease(ok, [])).toEqual([]);
});
