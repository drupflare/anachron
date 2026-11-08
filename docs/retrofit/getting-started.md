# Getting Started

anachron runs a Drupal 7 module or theme on Drupal 11 without a rewrite. The module still needs an `.info.yml` file, and the site needs to opt in to loading code that declares an older core.

1. Create a Drupal 11 site.
2. Add the package.
3. Copy the Drupal 7 modules and themes into the site.
4. Convert each `.info` file to `.info.yml`.
5. Turn on the legacy switches.
6. Enable the modules and migrate the data.

```sh
composer create-project drupal/recommended-project drupal
cd drupal
composer require drupflare/anachron
```

## Info Files

Rename `NAME.info` to `NAME.info.yml` and add `type`. With `anachron_ignore_core_requirement` on, an extension needs no `core_version_requirement`, and one that declares an older core still enables. The declared value stays in `anachron_core_requirement` on the extension's info array.

For a module:

```yaml
name: Example
type: module
```

A theme gets `type: theme`. Rename `template.php` to `THEME_NAME.theme`. Declare an asset library for the `stylesheets` the old `.info` file listed and attach it with a `libraries` entry.

## Switches

Both switches are off by default, and a site that loads the package behaves like stock Drupal 11.

| key                                | effect                                                                 |
| ---------------------------------- | ---------------------------------------------------------------------- |
| `anachron_ignore_core_requirement` | enables extensions whose `core_version_requirement` excludes Drupal 11 |
| `anachron_legacy_field_access`     | lets field items take Drupal 7 array access such as `$item['value']`   |

`anachron_ignore_core_requirement` must be set before Composer's autoloader runs. Core's extension discovery builds its info parser before the container exists, so the package replaces that class when it loads, and `settings.php` runs after that. Set the key from a file that runs first, such as the front controller or a PHP `auto_prepend_file`.

```php
$GLOBALS['conf']['anachron_ignore_core_requirement'] = true;
```

`anachron_legacy_field_access` is read when the container builds, so `$GLOBALS['conf']` and `settings.php` both work.

```php
$settings['anachron_legacy_field_access'] = true;
```

Passing the version check says nothing about whether the code works. See [Caveats](caveats.md).
