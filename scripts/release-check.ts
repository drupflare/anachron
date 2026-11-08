import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

const SEMVER = /^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/;

export function checkRelease(root: string, tags: string[]): string[] {
	const problems: string[] = [];
	const pkg = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8')) as {
		version?: string;
	};
	const composer = JSON.parse(readFileSync(join(root, 'composer.json'), 'utf8')) as {
		version?: string;
	};

	if (!pkg.version || !SEMVER.test(pkg.version)) {
		problems.push(`package.json version ${JSON.stringify(pkg.version)} is not semver`);
	} else if (tags.includes(`v${pkg.version}`)) {
		problems.push(`tag v${pkg.version} already exists`);
	}
	if (composer.version !== undefined) {
		problems.push('composer.json carries a version key; Packagist reads the tag');
	}

	const license = join(root, 'LICENSE');
	if (!existsSync(license) || !readFileSync(license, 'utf8').startsWith('MIT License')) {
		problems.push('LICENSE is missing or is not the MIT text');
	}

	if (existsSync(join(root, 'src', 'Drupal'))) {
		const notice = join(root, 'NOTICE');
		if (!existsSync(notice) || !readFileSync(notice, 'utf8').includes('Matt Glaman')) {
			problems.push(
				'src/Drupal holds copied Retrofit files and NOTICE does not attribute them'
			);
		}
	}
	return problems;
}

if (import.meta.main) {
	const root = process.cwd();
	const run = Bun.spawnSync(['git', 'tag'], { cwd: root });
	const tags = run.exitCode === 0 ? run.stdout.toString().split('\n').filter(Boolean) : [];
	const problems = checkRelease(root, tags);
	for (const p of problems) console.error(`release check: ${p}`);
	console.log(`${problems.length} release problem${problems.length === 1 ? '' : 's'}`);
	process.exit(problems.length === 0 ? 0 : 1);
}
