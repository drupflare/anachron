import { readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { join, relative } from 'node:path';

export interface Inventory {
	tree: string;
	files: number;
	hooks: string[];
	deprecations: { total: number; byRemoval: Record<string, number> };
	functions: string[];
	classes: string[];
	aliases: string[];
}

const SKIP = /(^|\/)(vendor|node_modules|\.git|tests?|Tests?|fixtures?)(\/|$)/;
const PHP = /\.(php|inc|module|install|theme|engine|profile|test)$/;
const HOOK_DEF = /^function (hook_[a-z0-9_]+)\s*\(/gm;
const REMOVAL =
	/(?:removed|removal)\s+(?:from|in)\s+(?:drupal:|joomla\s+|wordpress\s+)?v?(\d+)(?:\.(\d+))?/gi;
const DEPRECATED = /@deprecated\b([^\n]*(?:\n\s*\*[^\n@]*)*)/g;
const SYMBOLS = join(import.meta.dir, 'symbols.php');

function symbols(tree: string): { functions: string[]; classes: string[]; aliases: string[] } {
	const run = Bun.spawnSync(['php', '-d', 'memory_limit=2G', SYMBOLS, tree]);
	if (run.exitCode !== 0) throw new Error(`symbols.php failed: ${run.stderr.toString()}`);
	return JSON.parse(run.stdout.toString());
}

function walk(dir: string, root: string, out: string[]): void {
	for (const name of readdirSync(dir)) {
		const full = join(dir, name);
		const rel = relative(root, full);
		if (SKIP.test(rel)) continue;
		const st = statSync(full);
		if (st.isDirectory()) walk(full, root, out);
		else if (PHP.test(name)) out.push(full);
	}
}

function all(re: RegExp, text: string): RegExpMatchArray[] {
	return [...text.matchAll(re)];
}

export function inventory(tree: string): Inventory {
	const paths: string[] = [];
	walk(tree, tree, paths);
	const hooks = new Set<string>();
	const byRemoval: Record<string, number> = {};
	let total = 0;

	for (const path of paths) {
		const text = readFileSync(path, 'utf8');
		if (/\.api\.php$/.test(path)) {
			for (const m of all(HOOK_DEF, text)) if (m[1]) hooks.add(m[1]);
		}
		for (const d of all(DEPRECATED, text)) {
			total++;
			const key = [...(d[1] ?? '').matchAll(REMOVAL)][0]?.[1];
			if (key) byRemoval[key] = (byRemoval[key] ?? 0) + 1;
		}
	}

	const sym = symbols(tree);
	return {
		tree,
		files: paths.length,
		hooks: [...hooks].sort(),
		deprecations: { total, byRemoval },
		functions: sym.functions,
		classes: sym.classes,
		aliases: sym.aliases
	};
}

// a class name still reachable through an alias in the newer tree is not removed
export function removed(older: Inventory, newer: Inventory) {
	const gone = (a: string[], b: string[]) => {
		const keep = new Set(b);
		return a.filter((x) => !keep.has(x));
	};
	return {
		hooks: gone(older.hooks, newer.hooks),
		functions: gone(older.functions, newer.functions),
		classes: gone(older.classes, [...newer.classes, ...newer.aliases])
	};
}

if (import.meta.main) {
	const [cmd, a, b, out] = process.argv.slice(2);
	if (cmd === 'inventory' && a && b) {
		writeFileSync(b, JSON.stringify(inventory(a), null, '\t'));
	} else if (cmd === 'diff' && a && b && out) {
		const r = removed(JSON.parse(readFileSync(a, 'utf8')), JSON.parse(readFileSync(b, 'utf8')));
		writeFileSync(out, JSON.stringify(r, null, '\t'));
	} else {
		console.error(
			'usage: inventory.ts inventory <tree> <out.json> | diff <older.json> <newer.json> <out.json>'
		);
		process.exit(2);
	}
}
