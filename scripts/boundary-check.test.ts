import { expect, test } from 'bun:test';
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { checkBoundary } from './boundary-check';

function repo(files: Record<string, string>): string {
	const root = mkdtempSync(join(tmpdir(), 'boundary-'));
	for (const [path, body] of Object.entries(files)) {
		mkdirSync(join(root, path, '..'), { recursive: true });
		writeFileSync(join(root, path), body);
	}
	return root;
}

test('a neutral file that names no CMS passes', () => {
	const root = repo({ 'src/Php/a.php': '<?php\nfunction each() {}\n' });
	expect(checkBoundary(root)).toEqual([]);
});

test('each CMS marker in src/Php is reported with its file and line', () => {
	const root = repo({
		'src/Php/a.php':
			'<?php\nuse Drupal\\Core\\Foo;\n$a = \\Drupal::service("x");\n$b = new WP_Query();\nglobal $wpdb;\n$c = JFactory::getApplication();\nuse Joomla\\CMS\\Factory;\n'
	});
	const lines = checkBoundary(root).map((v) => v.line);
	expect(lines).toEqual([2, 3, 4, 5, 6, 7]);
});

test('src/Drupal may name Drupal, only src/Php is neutral', () => {
	const root = repo({
		'src/Php/a.php': '<?php\n',
		'src/Drupal/b.php': '<?php\nuse Drupal\\Core\\Foo;\n'
	});
	expect(checkBoundary(root)).toEqual([]);
});

test('a repository with no src/Php reports nothing', () => {
	expect(checkBoundary(repo({ 'README.md': 'x' }))).toEqual([]);
});
