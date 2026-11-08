# Namespaced Functions

Some functions sit under the `Drupflare\Anachron\Drupal` namespace. A global definition would collide with a function Drupal core still defines, or with one that core deprecated.

## `module_load_include`

Available as `Drupflare\Anachron\Drupal\module_load_include`. The global `module_load_include()` is also defined, in `legacy-removed.php`, for core releases that no longer have it.
