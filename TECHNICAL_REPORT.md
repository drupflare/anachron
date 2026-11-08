# Technical Report: Legacy CMS Code on a Current Core

What `drupflare/anachron` does, what the old code it targets looks like, and what was measured to find out. Written for maintainers. Every number carries its method. Where a figure came from a local machine and not a deployed Worker, it says so.

## Contents

- [Executive Summary](#executive-summary)
- [Source Trees](#source-trees)
- [Method](#method)
- [Break Catalog](#break-catalog)
- [Polyfill Reach](#polyfill-reach)
- [PHP Compatibility](#php-compatibility)
- [Transform Cost](#transform-cost)
- [Security Properties](#security-properties)
- [Instrument Rules](#instrument-rules)
- [Limits](#limits)

## Executive Summary

Old CMS code breaks in two ways. Its framework API changed, or PHP itself removed something it calls. anachron addresses both on a current core: it defines removed PHP functions under their original names, and it supplies the old framework functions, hooks and routes the code was written against.

Four findings shaped the design.

1. The break is a cliff followed by a staircase. Drupal 7 to 8 removes most of the API. Drupal 8 to 12 removes 100 to 650 tagged deprecations per step. Joomla 3 to 4 leaves 1,253 of 1,736 class names undefined and unaliased, and Joomla 4 to 6 leaves 19. WordPress core removes almost nothing.
2. Function polyfills reach most of the call volume in Drupal 7 code. In a sample of 43 contrib modules, excluding `t()`, an existing polyfill library defines 72.9% of the calls to core functions that Drupal 11 no longer has, 3.7% are names Drupal 11 still defines, and 23.4% are uncovered. The 155 most-called uncovered functions account for 80% of that remainder.
3. PHP removal is a separate, CMS-neutral problem. Drupal 7 core calls no removed function. A sample of 80 long-unmaintained WordPress plugins shows 6 of 80 with a hard PHP 8 break, from removed functions or removed syntax.
4. A parse-and-print transform costs about 3.5 to 3.9 ms per KB inside the interpreter, 1.27 times its native cost. That makes a source rewrite affordable once per revision and expensive on every cold boot.

The package currently ships four removed-function polyfills. The Drupal layer is built from a copy of Retrofit (MIT) and is not published yet.

## Source Trees

Counts come from shallow clones of release tags. The commit identifies each tree.

| tree            | tag          | commit     |
| --------------- | ------------ | ---------- |
| Drupal 7        | 7.103        | `e7242e52` |
| Drupal 8        | 8.9.20       | `e187f925` |
| Drupal 9        | 9.5.11       | `4576b6a4` |
| Drupal 10       | 10.6.21      | `d2200d92` |
| Drupal 11       | 11.4.11      | `76deda03` |
| Drupal 12       | 12.0.0-beta2 | `3df2505f` |
| WordPress 4     | 4.9.35       | `208496d6` |
| WordPress 5     | 5.9.20       | `50f0d0f0` |
| WordPress 6     | 6.9.11       | `669aaf29` |
| WordPress 7     | 7.1.4        | `db96f5ea` |
| Joomla 3        | 3.10.12      | `b0baf82c` |
| Joomla 4        | 4.4.14       | `e215057c` |
| Joomla 5        | 5.4.112      | `b3648130` |
| Joomla 6        | 6.2.0        | `d7b2268f` |
| retrofit-drupal | `main`       | `fe72017c` |

Drupal 12.0.0 final is not released at the time of writing. Every Drupal 12 figure is beta2.

## Method

`scripts/inventory` produces every count here. It reads a tree and reports hook names defined in `*.api.php`, `@deprecated` tags grouped by the first removal major in each tag, function and class names, and class aliases. Function and class names come from PHP's tokenizer (`token_get_all`). A guarded definition counts. Methods and closures don't. `class_alias` and `JLoader::registerAlias` names count as aliases, and a class that survives as an alias isn't reported as removed. `scripts/inventory/calls.php` counts calls to a given list of function names the same way, skipping method calls, static calls, definitions and `new`.

The scanner skips directories named `vendor`, `node_modules`, `.git`, `test`, `tests`, `Test`, `Tests`, `fixture` and `fixtures`.

Cross-checks against an independent `grep`:

| count                           | script | grep | note                                                                   |
| ------------------------------- | ------ | ---- | ---------------------------------------------------------------------- |
| Joomla 6.2.0 `@deprecated` tags | 683    | 683  |                                                                        |
| Drupal 7.103 hook definitions   | 354    | 354  |                                                                        |
| Drupal 11.4.11 hook definitions | 256    | 256  |                                                                        |
| Drupal 11.4.11 `@deprecated`    | 593    | 598  | six tags in `core/lib/Drupal/Core/Test/` (skipped), one `.install` tag |

A first version of the scanner used a line-anchored regular expression and missed indented function definitions. It listed 127 WordPress functions as removed between 4.9.35 and 7.1.4, and 116 were still defined. It was replaced and its output isn't used.

A name that is still defined doesn't mean behavior is preserved. A same-named function can have a different signature.

## Break Catalog

### Drupal 8 to 12

| step                    | tags that name this removal in the older tree | hooks removed / added | core modules removed                                                                                   | PHP minimum  | Symfony    | Twig         | jQuery         |
| ----------------------- | --------------------------------------------- | --------------------- | ------------------------------------------------------------------------------------------------------ | ------------ | ---------- | ------------ | -------------- |
| 8.9.20 to 9.5.11        | 619 for 9.x (651 total)                       | 9 / 8                 | block_place                                                                                            | 7.0.8 to 7.3 | 3.4 to 4.4 | 1.38 to 2.15 | 3.5.1 to 3.6.3 |
| 9.5.11 to 10.6.21       | 129 for 10.x (169 total)                      | 12 / 5                | aggregator, color, hal, quickedit, rdf, ckeditor 4, simpletest                                         | 7.3 to 8.1   | 4.4 to 6.4 | 2.15 to 3.28 | 3.6.3 to 3.7.1 |
| 10.6.21 to 11.4.11      | 225 for 11.x (245 total)                      | 4 / 14                | action, book, forum, help_topics, statistics, tour, tracker                                            | 8.1 to 8.3   | 6.4 to 7.4 | 3.28         | 3.7.1 to 4.0.0 |
| 11.4.11 to 12.0.0-beta2 | 378 for 12.x, none left in beta2 (593 total)  | 18 / 0                | ban, contact, field_layout, history, search, settings_tray, shortcut, telephone, toolbar, three themes | 8.3 to 8.5   | 7.4 to 8.1 | 3.28         | 4.0.0          |

In beta2, `Connection::insert()` is abstract (`Connection.php:833`) and `createConnectionOptionsFromUrl()` lost its `$root` argument. `ConfigFormBase::__construct` took `$typedConfigManager` as optional in 10.6.21 and as required in 11.4.11. `ContentEntityForm::__construct` lost the `NULL` defaults on two parameters between 8.9.20 and 9.5.11. Drupal 11 ships jQuery 4.0.0, which removes `jQuery.isArray` and `jQuery.parseJSON`. A hook can leave `*.api.php` and still be implemented: beta2's `CommentHooks.php:280` still says "Implements hook_node_update_index()".

A drawn sample of 60 items per line shows how much of the old surface survives by name in 11.4.11. The draw takes the first matches per stratum in `grep` order and leans toward modules that still exist, so the survival share is an upper bound.

| source line | still present by name | needs a mechanism | untranslatable |
| ----------- | --------------------- | ----------------- | -------------- |
| Drupal 8    | 33 of 60              | 14                | 13             |
| Drupal 9    | 39 of 60              | 8                 | 13             |
| Drupal 10   | 56 of 60              | 2                 | 2              |

### Drupal 7 to 11

Of 354 Drupal 7 hooks, 87 have the same name in 11.4.11 and 267 don't. 17 of the 267 are implemented in 11.4.11 through `#[Hook('<name>')]` (a floor: the check read single lines). A stratified sample of 44 of the 267, drawn across 23 modules, splits as follows.

| outcome                       | n   | share |
| ----------------------------- | --- | ----- |
| renamed                       | 19  | 43%   |
| replaced by another mechanism | 14  | 32%   |
| no successor                  | 11  | 25%   |

Weighted by stratum size, the estimate over all 267 is about 51%, 33% and 16%. Nineteen of the 23 strata have one or two picks, so the margin on any single figure is wide, roughly 15 points. Renamed hooks are mostly entity hooks that became templated `hook_ENTITY_TYPE_*` names. Replaced hooks became routes, YAML, plugins or config entities: `hook_menu`, `hook_permission`, `hook_block_info`, `hook_field_info`, `hook_node_info`. Hooks with no successor belong to modules gone from core.

Drupal 7 stores field definitions in `field_config` and `field_config_instance`, values in `field_data_*` and `field_revision_*`, and settings in `variable`. Drupal 11 keeps field definitions as config entities, names value tables `{entity_type}__{field}`, and splits `variable` between `key_value` and `Settings`. A module that reads or writes the old tables has no stock target.

Of 4,091 function names in Drupal 7.103, 3,759 are absent from 11.4.11, and so are all 470 class names. Most are internal to a core subsystem, so the call-weighted figures below describe real use better than this count does.

### WordPress

Core keeps deprecated functions. The three deprecation files define 252 functions in 4.9.35 and 321 in 7.1.4, and 16 of 16 sampled 4.9.35 names are still defined. Of 3,232 function names in 4.9.35, 24 are absent from 7.1.4. Minimum PHP rose from 5.2.4 to 5.6.20 to 7.2.24 to 7.4. jQuery went from 1.12.4 to 3.6.0 to 3.7.1, and core still loads jQuery Migrate 3.4.1. `wpdb` moved from ext/mysql and mysqli branches to mysqli only, and `prepare()` gained a variadic signature. PHPMailer and Requests moved into namespaces, and core aliases the old names.

### Joomla

Joomla 3.10.12 defines 1,736 class names. In 4.4.14, 1,253 are neither defined nor aliased. They cluster in the bundled FOF framework, legacy `JHtml*` helpers, legacy router rules, database driver shims and removed plugin classes. 4.4.14 to 5.4.112 drops 15 class names and 5.4.112 to 6.2.0 drops 4.

Joomla keeps old names alive with alias maps: 377 in the 3.10.12 core classmap, 456 `registerAlias` calls in 4.4.14, 452 in the 5.4.112 compat plugin, 450 in the 6.2.0 compat6 plugin. Each plugin covers the previous major only (`plg_behaviour_compat*.ini` in each tree). 3.10.12 has no behaviour compat plugin, so there was nothing to enable for the 3 to 4 move. In 6.2.0 the plugin is off by default, and its classmap tags say 6.0 while the plugin's own comment says 7.0.

Deprecation tags with a removal target: 3.10.12, 9 for 4.0 (1,127 total); 4.4.14, 478 for 6.0; 5.4.112, 516 for 6.0; 6.2.0, 605 for 7.0 and 37 for 8.0. Minimum PHP is 5.3.10, 7.2.5, 8.1.0 and 8.3.0. The front end moves from Bootstrap 2.3.2 and jQuery 1.12.4 to Bootstrap 5.3 and jQuery 3.7.

## Polyfill Reach

Retrofit (`retrofit-drupal/retrofit`, MIT) runs Drupal 7 code on Drupal 10. It defines 128 functions, which cover 127 of the 3,357 public-named Drupal 7 functions that Drupal 11 lacks (3.8% by name count). Its hook bridges are event subscribers, service decorators, a route subscriber and plugin derivers. It loads through composer `files` autoload and `$GLOBALS['conf']['container_service_providers']`, which core 11.4.11 and 12.0.0-beta2 both still read.

Call-weighted reach was measured on 43 well-known Drupal 7 contrib modules at their latest `7.x` branch: 1,789 files and 37,106 calls to Drupal 7 core functions. Contrib stands in for custom code and isn't the same population. This is a sample.

| group                                                     | calls  | share | share excluding `t()` | distinct functions |
| --------------------------------------------------------- | ------ | ----- | --------------------- | ------------------ |
| name still defined in Drupal 11 (`t()` is 18,879 of them) | 19,545 | 52.7% | 3.7%                  | 41                 |
| absent from Drupal 11, defined by Retrofit                | 13,295 | 35.8% | 72.9%                 | 115                |
| absent from Drupal 11, not defined by Retrofit            | 4,266  | 11.5% | 23.4%                 | 533                |
| total                                                     | 37,106 | 100%  | 100%                  | 689                |

Adding the 155 most-called uncovered functions would bring by-name coverage excluding `t()` to 72.9 + 3.7 + 0.8 × 23.4 = 95.3% (arithmetic). The most-called uncovered functions are `entity_get_info` (278), `drupal_get_destination` (116), `format_plural` (95), `format_date` (91), `drupal_strtolower` (79) and `arg` (78).

Retrofit doesn't adapt `form_alter`, `hook_menu_alter`, actions, tasks, `field_attach_*` or the `$node->field_x[LANG][delta]` array shape. Reading its code against 11.4.11 turned up six defects, none run: `module_implements()` calls a method core no longer has, `render()` and one preprocess function call functions deprecated for removal in 13, `node_load()` with no arguments loads every node, `user_role_load_by_name()` looks up by machine id, and `taxonomy_get_tree()` fails on default arguments. Its tests run on PHP 8.1 and have no Drupal 11 job. Support for Drupal 11 is an open issue and an unmerged pull request from April 2025.

What function polyfills can't do: add a method to a core class that still exists, change an extended class's constructor, or reshape field arrays. In the 60-item samples, 4 of 60 (Drupal 8), 5 of 60 (Drupal 9) and 1 of 60 (Drupal 10) items need a source rewrite.

## PHP Compatibility

Calls to 108 function names that PHP removed (`each`, `create_function`, `mysql_*`, `mcrypt_*`, `ereg*`, `split`, the magic-quotes functions and others), counted with the tokenizer.

| code                                                                               | files | calls | distinct |
| ---------------------------------------------------------------------------------- | ----- | ----- | -------- |
| Drupal 7.103 core                                                                  | 364   | 0     | 0        |
| 43 Drupal 7 contrib modules                                                        | 1,789 | 6     | 3        |
| WordPress 4.9.35 core                                                              | 853   | 52    | 24       |
| WordPress 5.9.20 core                                                              | 1,309 | 32    | 22       |
| Joomla 3.10.12 core                                                                | 2,773 | 83    | 32       |
| Joomla 4.4.14 core                                                                 | 2,845 | 17    | 11       |
| 80 WordPress plugins, four or more years without an update, 1,000 or more installs | 787   | 6     | 2        |

The cores contain guarded fallbacks that never run on modern PHP, so their counts overstate live calls. The plugin set is every match across eight popularity pages of the WordPress.org plugin API (pages 20 through 90 in steps of ten, 100 per page, sorted by popularity), minus one download that timed out. It's a systematic draw, not a random one.

PHP 8.5.7 compile-time messages (`php -l`):

- 43 Drupal 7 contrib modules: 24 of 1,789 files emit a message. None fail to parse. The messages are implicit nullable parameter types, non-canonical casts, `case` followed by a semicolon, and optional parameters before required ones.
- 80 WordPress plugins: 21 of 787 files emit a message and 2 fail to parse. Both parse errors report an unexpected `{`, which fits the `$string{0}` offset syntax that PHP 8.0 removed. That wasn't checked line by line.

Five of the 80 plugins (6.3%) have a hard PHP 8 break: three call a removed function and two don't parse.

A function polyfill covers `each` and the magic-quotes functions, which are the removed calls Drupal 7 code makes. It can cover `create_function` only through `eval`, so `src/Php` leaves it undefined. It can't cover a parse error. A token-level rewrite can: `$s{0}` becomes `$s[0]` using `token_get_all` alone, with no parser. Neither part names a CMS. Both belong in `src/Php`, and `bun run boundary` fails the build if a file there references Drupal, Joomla or WordPress.

## Transform Cost

A source rewrite for what polyfills can't reach (removed static calls, constructor signature changes) means parsing PHP and printing it back. The cost was measured with `nikic/php-parser` 5.9.0: parse, rewrite two static calls, and print with the format-preserving printer. Each cell is five fresh processes, run natively and inside the shipping interpreter (PHP 8.5 compiled to WebAssembly) under node at `memory_limit=96M`. These are local V8 wall-clock figures, so read the ratio and not the absolute number.

| corpus                            | files | bytes     | native, mean (range)      | interpreter, mean (range) | ratio |
| --------------------------------- | ----- | --------- | ------------------------- | ------------------------- | ----- |
| Drupal 9.5.11 `core/modules/node` | 99    | 403,697   | 1,078 ms (910 to 1,213)   | 1,368 ms (1,209 to 1,647) | 1.27  |
| Drupal 7 `ctools`                 | 263   | 1,540,895 | 4,594 ms (4,116 to 5,336) | 5,863 ms (5,613 to 6,313) | 1.28  |

That's about 3.5 to 3.9 ms per KB inside the interpreter, and no corpus produced a parse error. Single runs on two larger corpora took 5.2 s (Drupal 9.5.11 `core/modules/views`, 1.64 MB) and 7.3 s (Drupal 7 `views`, 2.06 MB, including one 210 KB file that took 0.8 s). The lexer is PHP's C tokenizer, so the interpreter tax is small. Native peak memory was 12.6 MB for the 400 KB corpus, 18.9 MB for 1.5 MB, and 39.8 MB for the corpus with the 210 KB file. The interpreter's allocator reports 0 from `memory_get_peak_usage`, so peak memory inside it isn't measured.

A 400 KB module costs about 1.4 s and a 1.5 MB module about 5.9 s. Paid once per revision and cached by content hash, that's affordable. Paid on every cold boot, it adds seconds to a kernel boot that already takes several on a large Drupal site.

## Security Properties

All `local source`, Drupal 7.103 and 11.4.11.

- **Access fails closed in both.** Drupal 7 `menu.inc:3801-3807` gives a menu item with no `access arguments` and no `access callback` an access callback of 0. Drupal 11 `AccessManager.php:131,139` starts a route with no access requirement at neutral, and `isAllowed()` returns false. A translation that can't represent an old access callback must produce a deny. `_access: TRUE` would fail open.
- **Password hashes.** Drupal 11's default password service uses `password_verify`. Drupal 7 `$S$` hashes verify only with the `phpass` module enabled, which decorates the `password` service and isn't in the standard profile. After a successful login `UserAuth` rehashes (`UserAuth.php:57-58`).
- **Code execution.** Drupal 7 core had a PHP input filter that calls `eval` (`php.module:80`). Drupal 11 core doesn't. anachron doesn't provide it.
- **Stream wrapper.** Drupal 7 disables the `phar://` wrapper at bootstrap (`bootstrap.inc:715-724`). Drupal 11 `core/lib/Drupal` has no equivalent.
- **Unserialize.** Both unserialize stored data without `allowed_classes` (`PhpSerialize.php:21`, `cache.inc:449`).
- **SQL.** In Drupal 7, 13 `db_query` lines in 6 files concatenate identifiers. Values use placeholders. Drupal 11's `query()` only type-checks the query string.
- **Request state.** Drupal 7 code reads `$user` (133 uses), `$language` (40), `$conf` (15) and `$_SESSION` (262) and calls `drupal_static_reset` 200 times. A host that keeps PHP alive between requests must reset every static carrier the layer adds.

## Instrument Rules

- Figures from a tree are labelled with the tag and a path and line. Figures from a drawn sample say how the sample was drawn and its n.
- A figure from a web page is quoted from the page. A page read through a summarizing fetch tool isn't byte-checked, and a table read only as labels isn't quoted.
- Local wall clock gives a ratio. An absolute timing needs a deployed Worker with n and spread, and none is reported here.
- A negative result closes a mechanism. It doesn't close the goal.
- A guard that can't fire is a defect. Each check in `scripts/` has a test with a negative control.

## Limits

- No institution-level share of sites on Drupal 7, on unsupported WordPress, or on Joomla 3 was measured.
- The Drupal 7 call counts come from contrib modules, not custom code. The WordPress set is a popularity-page draw and its percentages depend on stratum.
- Retrofit's behavior beyond reading its code and a clean lint is not measured. Its classes haven't been loaded against Drupal 11.4 or 12 here.
- Transform cost has no deployed `cpuTime`, no reading for a single file near the memory limit, and no memory figure from inside the interpreter.
- The hook-outcome split rests on 44 hooks across 23 strata.
- Drupal 12 figures are beta2. The release candidate and final may differ.
- Drupal 7 hooks, functions and classes counted as absent are absent by name. A renamed or replaced hook counts as absent.
- Joomla's manifest fields beyond `targetplatform` and `php_minimum`, and WordPress and Joomla behavior beyond the counts above, are not covered.
