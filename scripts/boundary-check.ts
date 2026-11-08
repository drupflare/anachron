import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const FORBIDDEN = [
	/\bDrupal\\/,
	/\\Drupal::/,
	/\bJoomla\\/,
	/\bJFactory\b/,
	/\bwpdb\b/,
	/\bWP_[A-Za-z_]+\b/
];

export interface Violation {
	file: string;
	line: number;
	text: string;
}

function php(dir: string, out: string[]): void {
	for (const name of readdirSync(dir)) {
		const path = join(dir, name);
		if (statSync(path).isDirectory()) php(path, out);
		else if (name.endsWith('.php')) out.push(path);
	}
}

export function checkBoundary(root: string): Violation[] {
	const neutral = join(root, 'src', 'Php');
	const files: string[] = [];
	try {
		php(neutral, files);
	} catch {
		return [];
	}
	const found: Violation[] = [];
	for (const file of files) {
		readFileSync(file, 'utf8')
			.split('\n')
			.forEach((text, i) => {
				if (FORBIDDEN.some((re) => re.test(text))) {
					found.push({ file: relative(root, file), line: i + 1, text: text.trim() });
				}
			});
	}
	return found;
}

if (import.meta.main) {
	const found = checkBoundary(process.cwd());
	for (const v of found) console.error(`${v.file}:${v.line}: names a CMS in src/Php: ${v.text}`);
	console.log(`${found.length} boundary violation${found.length === 1 ? '' : 's'}`);
	process.exit(found.length === 0 ? 0 : 1);
}
