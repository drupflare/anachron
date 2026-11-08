# Caveats

## Passing the Version Check

A module that enables is not a module that works. `anachron_ignore_core_requirement` removes the version refusal and nothing else. Code that calls an API with no replacement fails when it runs. The `legacy` test suite lists each such API as a skipped test with its reason.

## Escaped Output

Drupal 7 had mechanisms for filtering user input by hand. Twig removed them because it escapes automatically. A PHPTemplate file that prints a value containing user input may not filter it. See [retrofit-drupal/retrofit#15](https://github.com/retrofit-drupal/retrofit/issues/15).

## Placeholders in `t()`

The `!` placeholder is no longer valid. Change each one to `@` or `:` by hand.

## Namespaced Functions

See [Namespaced Functions](namespaced-functions.md).

## Persistent Interpreters

A host that keeps PHP alive between requests must reset what the layer adds. `$user`, `$language`, `$language_content` and `$language_url` outlive a request unless their setters carry the `drupflare.reset` tag, which they do. A host that resets tagged services between requests clears them.
