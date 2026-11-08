import {
	cpSync,
	existsSync,
	mkdirSync,
	readdirSync,
	readFileSync,
	statSync,
	writeFileSync
} from 'node:fs';
import { join } from 'node:path';

const TEXT = /\.(php|yml|yaml|md|twig|neon|json|xml)$/;

const REWRITES: [RegExp, string][] = [
	[/Retrofit\\\\Drupal\\\\Tests/g, 'Drupflare\\\\Anachron\\\\Tests\\\\Drupal'],
	[/Retrofit\\Drupal\\Tests/g, 'Drupflare\\Anachron\\Tests\\Drupal'],
	[/Retrofit\\\\Drupal/g, 'Drupflare\\\\Anachron\\\\Drupal'],
	[/Retrofit\\Drupal/g, 'Drupflare\\Anachron\\Drupal'],
	[/mglaman\\DrupalTestHelpers\\/g, 'Drupflare\\Anachron\\Tests\\Drupal\\Utils\\'],
	[/tests\/data/g, 'tests/fixtures/retrofit']
];

const MAP: [string, string][] = [
	['src', 'src/Drupal'],
	['bootstrap.php', 'src/Drupal/bootstrap.php'],
	['templates', 'src/templates'],
	['tests/src', 'tests/Drupal'],
	['tests/data', 'tests/fixtures/retrofit'],
	['docs', 'docs/retrofit']
];

const AUTOLOAD_FILES = [
	'src/Drupal/constants.php',
	'src/Drupal/functions.php',
	'src/Drupal/bootstrap.php'
];

export function rewrite(dir: string): number {
	let count = 0;
	for (const name of readdirSync(dir)) {
		const path = join(dir, name);
		if (statSync(path).isDirectory()) {
			count += rewrite(path);
		} else if (TEXT.test(name)) {
			const before = readFileSync(path, 'utf8');
			let after = before;
			for (const [from, to] of REWRITES) after = after.replace(from, to);
			if (after !== before) {
				writeFileSync(path, after);
				count++;
			}
		}
	}
	return count;
}

export function importRetrofit(from: string, to: string): { rewritten: number; sha: string } {
	const manifest = join(from, 'composer.json');
	if (!existsSync(manifest)) throw new Error(`${from} has no composer.json`);
	const name = (JSON.parse(readFileSync(manifest, 'utf8')) as { name?: string }).name;
	if (name !== 'retrofit-drupal/retrofit') throw new Error(`${from} is ${name}, not retrofit`);
	if (existsSync(join(to, 'src', 'Drupal'))) throw new Error('src/Drupal already exists');

	let rewritten = 0;
	for (const [source, target] of MAP) {
		const src = join(from, source);
		if (!existsSync(src)) continue;
		const dst = join(to, target);
		mkdirSync(join(dst, '..'), { recursive: true });
		cpSync(src, dst, { recursive: true, filter: (p) => !p.includes('/.git') });
		if (statSync(dst).isDirectory()) rewritten += rewrite(dst);
		else {
			const before = readFileSync(dst, 'utf8');
			let after = before;
			for (const [pattern, replacement] of REWRITES)
				after = after.replace(pattern, replacement);
			if (after !== before) {
				writeFileSync(dst, after);
				rewritten++;
			}
		}
	}

	const git = Bun.spawnSync(['git', '-C', from, 'rev-parse', 'HEAD']);
	const sha = git.exitCode === 0 ? git.stdout.toString().trim() : 'unknown';
	const license = readFileSync(join(from, 'LICENSE'), 'utf8').trimEnd();
	writeFileSync(
		join(to, 'NOTICE'),
		`Files under src/Drupal, src/templates, tests/Drupal, tests/fixtures/retrofit and docs/retrofit are\n` +
			`copied from retrofit-drupal/retrofit (https://github.com/retrofit-drupal/retrofit), commit ${sha},\n` +
			`with the namespace rewritten. They remain under the licence below.\n\n${license}\n`
	);

	const composerPath = join(to, 'composer.json');
	if (existsSync(composerPath)) {
		const composer = JSON.parse(readFileSync(composerPath, 'utf8')) as {
			autoload?: { files?: string[] };
		};
		const files = new Set(composer.autoload?.files ?? []);
		for (const f of AUTOLOAD_FILES) if (existsSync(join(to, f))) files.add(f);
		composer.autoload = { ...composer.autoload, files: [...files] };
		writeFileSync(composerPath, JSON.stringify(composer, null, '\t') + '\n');
	}
	return { rewritten, sha };
}

if (import.meta.main) {
	const from = process.argv[2];
	if (!from) {
		console.error('usage: bun scripts/import-retrofit.ts <retrofit checkout> [target repo]');
		process.exit(2);
	}
	const result = importRetrofit(from, process.argv[3] ?? process.cwd());
	console.log(`imported at ${result.sha}, ${result.rewritten} files rewritten`);
}
