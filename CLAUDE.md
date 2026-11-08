# anachron

Runs legacy CMS code on a current core. v0.x is Drupal only and is tested against drupflare v1.1 batches. v1 ships with drupflare v1.1 and adds WordPress and Joomla, built in that order so the shared layer is extracted from the WordPress implementation. No `CmsRuntime` interface exists before that.

## Layout

- `src/Php/`: CMS-neutral PHP compatibility. It names no CMS (`bun run boundary` enforces it) so it can move to its own package. `polyfills.php` is autoloaded through composer `files`.
- `src/Drupal/`: the Drupal layer. Populated by `bun run import:retrofit <checkout>` from retrofit-drupal/retrofit (MIT, soft fork: files copied, namespace rewritten, attribution in `NOTICE`). Never a GitHub fork. Every copied file keeps Matt Glaman's licence through `NOTICE`; `release:check` fails without it.
- `tests/Unit/`, `tests/Drupal/`, `tests/Legacy/`: the PHPUnit suites `unit`, `integration` and `legacy`. `tests/Legacy` runs fixture modules from `tests/fixtures/legacy/{d7,d8,d9,d10}` on Drupal 11 and lists each API with no replacement as a skipped test. `tests/fixtures/retrofit/` came with the import. `tests/lint.php` is `php -l` over everything. `tests/load-classes.php` loads every class against a real Drupal root.
- `docs/retrofit/`: public pages for the Drupal layer. `api/` is phpDocumentor output (`bun run docs`), ignored by git and published to `gh-pages` by `.github/workflows/docs.yml` through `phpdoc.sh`. `.phpdoc/template` holds the favicons.
- `scripts/`: Bun tooling with its own tests. `scripts/inventory/` produces the counts `TECHNICAL_REPORT.md` quotes.
- `TECHNICAL_REPORT.md`: the public record. `docs/` is for public documentation only. Research specs, market notes, roadmap drafts and platform-integration findings stay in project memory, never in the repository.

WordPress and Joomla code ships as separate GPL-2.0-or-later packages under `packages/` when those adapters start. MIT stays everywhere it can.

## Gates

`bun run lint`, `bun run lint:php`, `bun run analyze`, `bun run test`, `bun run test:classes <drupal-root>`, `bun run boundary`, `bun run typecheck`, `bun run prettier:check`, `bun run docs`. The `legacy` job in `build.yml` runs `phpunit --testsuite legacy`. `phpcs.xml.dist` excludes the imported tree and `tests/fixtures`, with the reason beside each pattern, and `phpstan-baseline.neon` holds the imported code's remaining errors. Do not run `phpcbf` over the whole repository: it inserted empty docblocks and a short class name that broke `legacy-classes.php`. CI runs the same on PHP 8.3, 8.4 and 8.5, and loads classes against Drupal 11.4.11 and 12.0.0-beta2 (the beta is allowed to fail until RC1). The release workflow calls the build workflow, then `release:check`, then tags from `package.json`.

## Rules

- Evidence labels on every figure in `TECHNICAL_REPORT.md`: `full text`, `local source`, `sample` (with n), `arithmetic`. A figure with no sentence behind it is not quoted. Load the `avoid-ai-writing` skill before writing or editing any documentation.
- A name that is still defined is not behavior that is preserved. Never call a module compatible because it passes a version check or a function name resolves.
- An access callback that cannot be translated is a deny, never an allow. Drupal 7 and Drupal 11 both fail closed on a missing access rule; keep it that way.
- Absolute timings come from a deployed Worker with n and spread. Local wall clock is a ratio, labelled.
- Style follows `~/.claude/CLAUDE.md`: tabs, 100 columns, lowercase `true`, `false`, `null`, `use` then the short name, no comments in configuration files. Drupal code matches mantle2.
- The counts are regular-expression and tokenizer counts over the pinned tags in `TECHNICAL_REPORT.md`. Re-run `bun run inventory` when a tree changes. `composer.lock` is not committed because this is a library; `bun.lock` is.
