# anachron

Runs legacy Drupal code on a current Drupal core. Old modules keep calling the functions and hooks they were written against, and anachron supplies them on Drupal 11.

## Installation

```sh
composer require drupflare/anachron
```

The package autoloads its PHP compatibility functions. It needs PHP 8.3 or later and Drupal 11.3 or later.

## PHP Compatibility

`src/Php` defines functions that PHP removed, under their original names and only when the host has not defined them.

| function                     | behavior                                                                    |
| ---------------------------- | --------------------------------------------------------------------------- |
| `each()`                     | returns the current key and value, advances the pointer, false past the end |
| `get_magic_quotes_gpc()`     | false                                                                       |
| `get_magic_quotes_runtime()` | false                                                                       |
| `set_magic_quotes_runtime()` | true when asked to disable, false when asked to enable                      |

`create_function()` and the `mysql_*` family are not defined. The first needs `eval`, and the second needs a database connection the host owns.

## Technical Report

`TECHNICAL_REPORT.md` records what the legacy code looks like, what each count and timing measured, and the limits of each.

## Requirements

- PHP 8.3 or later with the `tokenizer` extension
- Drupal 11.3 or later

## License

MIT. See `LICENSE`. Files copied from other projects carry their own notice in `NOTICE`.
