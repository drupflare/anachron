import { expect, test } from 'bun:test';
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { inventory, removed } from './inventory';

function tree(files: Record<string, string>): string {
	const root = mkdtempSync(join(tmpdir(), 'inv-'));
	for (const [path, body] of Object.entries(files)) {
		const full = join(root, path);
		mkdirSync(join(full, '..'), { recursive: true });
		writeFileSync(full, body);
	}
	return root;
}

const OLD = tree({
	'modules/system/system.api.php':
		'<?php\nfunction hook_menu() {}\nfunction hook_block_info() {}\n',
	'includes/common.inc':
		'<?php\nfunction drupal_set_message($m) {}\nfunction variable_get($n) {}\n',
	'vendor/x/y.php': '<?php\nfunction hook_hidden() {}\n',
	'tests/t.php': '<?php\nfunction hook_in_tests() {}\n'
});

const NEW = tree({
	'core/modules/system/system.api.php': '<?php\nfunction hook_block_info() {}\n',
	'core/lib/Foo.php':
		'<?php\nnamespace Drupal\\Core;\nfinal class Foo {}\n/**\n * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use Bar.\n */\nfunction old_thing() {}\n/**\n * @deprecated in drupal:9.0.0 and is removed from drupal:11.0.0.\n */\nfunction other() {}\n/**\n * @deprecated without a removal target\n */\nfunction third() {}\n'
});

test('hook definitions come from api files only, skipping vendor and tests', () => {
	expect(inventory(OLD).hooks).toEqual(['hook_block_info', 'hook_menu']);
});

test('procedural functions and namespaced classes are collected', () => {
	expect(inventory(OLD).functions).toContain('variable_get');
	expect(inventory(NEW).classes).toEqual(['Drupal\\Core\\Foo']);
});

test('deprecations are grouped by removal major, unmarked ones counted in total', () => {
	const d = inventory(NEW).deprecations;
	expect(d.total).toBe(3);
	expect(d.byRemoval).toEqual({ '11': 2 });
});

test('removed reports names present in the older tree and absent from the newer', () => {
	const r = removed(inventory(OLD), inventory(NEW));
	expect(r.hooks).toEqual(['hook_menu']);
	expect(r.functions).toContain('drupal_set_message');
	expect(r.functions).not.toContain('old_thing');
});

const GUARDED = tree({
	'a.php':
		"<?php\nif ( ! function_exists( 'wp_mail' ) ) :\n\tfunction wp_mail() {}\nendif;\nclass Box {\n\tpublic function method_one() { $f = function () {}; }\n\tfunction method_two() {}\n}\nfunction &by_ref() {}\n$c = fn() => 1;\necho Foo::class;\n",
	'b.php':
		"<?php\nclass_alias( Real\\Thing::class, 'OldThing' );\nJLoader::registerAlias('JRegistry', '\\\\Joomla\\\\Registry\\\\Registry', '6.0');\n"
});

test('functions include guarded and by-reference definitions, never methods or closures', () => {
	const inv = inventory(GUARDED);
	expect(inv.functions).toEqual(['by_ref', 'wp_mail']);
	expect(inv.classes).toEqual(['Box']);
});

test('aliases are collected and keep a class from counting as removed', () => {
	const older = { ...inventory(GUARDED), classes: ['OldThing', 'Gone'], aliases: [] };
	const newer = inventory(GUARDED);
	expect(newer.aliases).toEqual(['JRegistry', 'OldThing']);
	expect(removed(older, newer).classes).toEqual(['Gone']);
});

test('negative control: a tree with no matching syntax reports nothing', () => {
	const empty = tree({ 'a.php': '<?php\n$x = 1;\n' });
	const inv = inventory(empty);
	expect(inv.hooks).toEqual([]);
	expect(inv.deprecations.total).toBe(0);
});
