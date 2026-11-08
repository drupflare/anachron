import { expect, test } from 'bun:test';
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const CALLS = join(import.meta.dir, 'calls.php');

function run(files: Record<string, string>, names: string[]) {
	const root = mkdtempSync(join(tmpdir(), 'calls-'));
	for (const [path, body] of Object.entries(files)) {
		mkdirSync(join(root, path, '..'), { recursive: true });
		writeFileSync(join(root, path), body);
	}
	const list = join(root, 'list.json');
	writeFileSync(list, JSON.stringify(names));
	const out = Bun.spawnSync(['php', CALLS, root, list]);
	return JSON.parse(out.stdout.toString());
}

test('counts plain calls, ignoring methods, static calls, definitions and instantiations', () => {
	const r = run(
		{
			'a.module':
				'<?php\nfunction node_load($n) {}\nnode_load(1); $x->node_load(2); Foo::node_load(3); new node_load(); variable_get("a"); variable_get("b");\n',
			'tests/skipped.module': '<?php\nvariable_get("z");\n'
		},
		['node_load', 'variable_get', 'never_called']
	);
	expect(r.calls).toEqual({ variable_get: 2, node_load: 1 });
	expect(r.files).toBe(1);
});

test('a name that is not on the list is never counted', () => {
	const r = run({ 'b.inc': '<?php\nother_fn();\n' }, ['node_load']);
	expect(r.calls).toEqual([]);
});
