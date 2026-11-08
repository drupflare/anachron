# 🕰️ anachron

> Runs legacy Drupal code on a current Drupal core

[![Build](https://github.com/drupflare/anachron/actions/workflows/build.yml/badge.svg)](https://github.com/drupflare/anachron/actions/workflows/build.yml)
[![Coverage](https://github.com/drupflare/anachron/actions/workflows/coverage.yml/badge.svg)](https://github.com/drupflare/anachron/actions/workflows/coverage.yml)
[![Prettier](https://github.com/drupflare/anachron/actions/workflows/prettier.yml/badge.svg)](https://github.com/drupflare/anachron/actions/workflows/prettier.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Old modules keep calling the functions and hooks they were written against, and anachron supplies
them on Drupal 11. It defines the functions PHP removed, adapts Drupal 7 hooks, routes, fields and
forms to their Drupal 11 equivalents, and lets a site enable extensions that declare an older core.

> [!IMPORTANT]
> **Requires PHP 8.3 or newer and Drupal 11.3 or newer.** Every class loads against Drupal
> 11.4.11 and 12.0.0-beta2. The kernel tests run on Drupal 11.4.

> [!CAUTION]
> **Passing the version check says nothing about whether the code works.** A module that enables
> can still call an API Drupal removed without a replacement. The [legacy suite](#-testing) runs
> fixture modules from Drupal 7 through 10 and reports each such API as a skipped test with its
> reason.

---

## 📋 Table of Contents

- [What It Provides](#-what-it-provides)
- [How It Loads](#-how-it-loads)
- [Installation](#-installation)
- [Legacy Sites](#-legacy-sites)
- [PHP Compatibility](#-php-compatibility)
- [Testing](#-testing)
- [Documentation](#-documentation)
- [Related Repositories](#-related-repositories)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🧩 What It Provides

| Layer  | Path         | What it does                                                                        |
| ------ | ------------ | ----------------------------------------------------------------------------------- |
| PHP    | `src/Php`    | defines functions PHP removed, under their original names, when the host lacks them |
| PHP    | `src/Php`    | rewrites removed syntax such as `$string{0}` to `$string[0]` with the PHP tokenizer |
| Drupal | `src/Drupal` | Drupal 7 functions, globals, `hook_menu`, field, block and form hooks, theme layer  |
| Drupal | `src/Drupal` | opt-in switches for the version check and for Drupal 7 field array access           |

`src/Php` names no CMS, so it can move to its own package. `src/Drupal` started as a copy of
[Retrofit](https://github.com/retrofit-drupal/retrofit) and is ported to Drupal 11.4 and 12.

---

## 🔧 How It Loads

Composer loads four files from `autoload.files`: `src/Php/polyfills.php`, `src/Drupal/constants.php`,
`src/Drupal/functions.php` and `src/Drupal/bootstrap.php`. The last one registers
`Drupflare\Anachron\Drupal\Provider` as a container service provider through
`$GLOBALS['conf']['container_service_providers']['retrofit']`, and the provider decorates the core
services that Drupal 7 code needs to see differently: the info parser, the menu link, local task
and local action managers, the theme registry, the library discovery, the field type manager, the
form builder, the entity type manager and the permission provider, among others.

Every function is guarded with `function_exists()`, so a function that core still defines wins.

---

## 📦 Installation

```sh
composer require drupflare/anachron
```

Copy the legacy module into the site and rename its `NAME.info` file to `NAME.info.yml`, adding
`type`. A module that declares an older core needs the version switch below to enable. The
[getting started page](docs/retrofit/getting-started.md) lists the steps for modules and themes.

---

## ⚙️ Legacy Sites

Two switches are off by default, so a site that loads the package behaves like stock Drupal 11.

| key                                | effect                                                                 |
| ---------------------------------- | ---------------------------------------------------------------------- |
| `anachron_ignore_core_requirement` | enables extensions whose `core_version_requirement` excludes Drupal 11 |
| `anachron_legacy_field_access`     | lets field items take Drupal 7 array access such as `$item['value']`   |

With the version switch on, an extension needs no `core_version_requirement`. The declared value
stays in `anachron_core_requirement` on the extension's info array, so a report can say how many
extensions ran past what they declared.

Core's extension discovery builds its info parser before the container exists, so the version
switch has to be set before Composer's autoloader runs. `settings.php` is too late for it. Set
the key from the front controller or from a PHP `auto_prepend_file`.

```php
$GLOBALS['conf']['anachron_ignore_core_requirement'] = true;
```

The field switch is read when the container builds, and `settings.php` works for it.

```php
$settings['anachron_legacy_field_access'] = true;
```

Wrapping a field item hides the interfaces of the item underneath, which is why the field switch
is opt-in.

---

## 🐘 PHP Compatibility

`src/Php` defines functions that PHP removed, only when the host has not defined them.

| function                     | behavior                                                                    |
| ---------------------------- | --------------------------------------------------------------------------- |
| `each()`                     | returns the current key and value, advances the pointer, false past the end |
| `get_magic_quotes_gpc()`     | false                                                                       |
| `get_magic_quotes_runtime()` | false                                                                       |
| `set_magic_quotes_runtime()` | true when asked to disable, false when asked to enable                      |

`create_function()` and the `mysql_*` family are not defined. The first needs `eval`, and the
second needs a database connection the host owns.

`Drupflare\Anachron\Php\SyntaxRewriter::rewrite()` takes PHP source and returns it with removed
syntax replaced. It uses `token_get_all()` alone and keeps the line count.

---

## 🧪 Testing

PHPUnit has three suites.

| suite         | what it covers                                                           |
| ------------- | ------------------------------------------------------------------------ |
| `unit`        | the PHP layer, the rewriter and the Drupal layer's classes in isolation  |
| `integration` | the Drupal layer inside a kernel test, with the container and a database |
| `legacy`      | fixture modules written for Drupal 7, 8, 9 and 10, running on Drupal 11  |

```sh
composer install
./vendor/bin/phpunit --testsuite unit,integration
./vendor/bin/phpunit --testsuite legacy --display-skipped
```

`php tests/load-classes.php <drupal-root>` loads every class against a Drupal checkout, one process
per class, because a class that extends something core removed passes a lint run and fatals on
first use. CI runs it against Drupal 11.4.11 and 12.0.0-beta2.

---

## 📚 Documentation

- [Drupal layer](docs/retrofit/README.md): getting started, supported APIs, hooks and globals, and caveats
- [Technical Report](TECHNICAL_REPORT.md): what the legacy code looks like, and what each count and timing measured
- [Security Policy](SECURITY.md): what counts as a vulnerability and how to report one
- API reference: built from the source docblocks with `bun run docs`

---

## 🔗 Related Repositories

| Repository                                                      | What it is                                                                          |
| --------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| [`drupflare/worker`](https://github.com/drupflare/worker)       | the site: the Worker, the Durable Object and the batches anachron is tested against |
| [`drupflare/drupflare`](https://github.com/drupflare/drupflare) | the Drupal module that satisfies platform needs with Workers bindings               |
| [`drupflare/rom`](https://github.com/drupflare/rom)             | the Drupal 11 database driver for Durable Object SQLite                             |

anachron does not require any of them. It runs on any Drupal 11 site.

---

## 🤝 Contributing

```sh
composer install
bun install

bun run lint     # php -l over every file
bun run lint:php # phpcs
bun run analyze  # phpstan level 5
bun run test     # phpunit, then the tooling tests
bun run test:classes /path/to/drupal
bun run boundary # src/Php may not name a CMS
bun run docs     # phpDocumentor into api/
bun run prettier:check
```

Files copied from other projects carry their own notice in `NOTICE`. `bun run import:retrofit`
refuses a second run, so change the copied files in place.

---

## 📄 License

MIT (c) Gregory Mitchell 2026. See [LICENSE](LICENSE). Retrofit's MIT notice is in
[NOTICE](NOTICE).
