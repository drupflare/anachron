# Supported APIs

What each [Drupal 7 API](https://www.drupal.org/docs/7/api) maps onto in Drupal 11. Class names are relative to `Drupflare\Anachron\Drupal`.

## Menu System

Drupal 7 routed requests and built menu links from one hook. Drupal 11 has a routing system and a menu link API. `hook_menu` is converted into routes and menu link plugins, and local tasks and local actions get derivers of their own. See [`hook_menu`](supported-hooks.md#hook_menu).

## Theme API

- Theme functions and PHPTemplate files are supported.
- Process hooks run as preprocess hooks.
- `Theme\Registry` builds the registry entries for them, and `Theme\HookPreprocess` runs the preprocess step.
- Block preprocessing is skipped for a block with no id or no block entity.

## Block API

- `Plugin\Derivative\BlockDeriver`
- `Plugin\Block\Block`

See [block hooks](supported-hooks.md#block-hooks).

## Field API

- `Field\FieldTypePluginManager`
- `Plugin\Derivative\FieldItemDeriver`
- `Plugin\Derivative\FieldFormatterDeriver`
- `Plugin\Field\FieldType\FieldItem`
- `Plugin\Field\FieldType\FieldItemList`
- `Plugin\Field\FieldType\DecoratedFieldItem`
- `Plugin\Field\FieldType\DecoratedOptionsFieldItem`

`DecoratedFieldItem` gives a field item Drupal 7 array access. It applies only with `anachron_legacy_field_access` on, and never to an entity reference item. A decorator hides the interfaces of the item it wraps, so options items use `DecoratedOptionsFieldItem`, which passes `OptionsProviderInterface` through.

See [field hooks](supported-hooks.md#field-hooks).

## Form API

- `Controller\DrupalGetFormController`
- `Form\DrupalGetForm`
- `Form\ArrayAccessFormState`, a form state that takes Drupal 7 array access
- `Form\FormBuilder`

## Entity API

`entity_load()` takes a Drupal 8 style scalar id or a Drupal 7 style array of ids and answers in the shape the caller used. `Entity\WrappedContentEntity` reads and writes a field's first value as a property, as `$node->field_x` did. `Entity\WrappedConfigEntity` does the same for config entities.

## Functions

Function files live in `src/Drupal/functions`, one per Drupal 7 include. Two files cover what the rest of the layer does not.

`ranked.php` defines 25 Drupal 7 core functions from the most-called list in the [Technical Report](../../TECHNICAL_REPORT.md#polyfill-reach). Retrofit never defined them and Drupal 11 lacks them:

`drupal_strlen`, `drupal_strtolower`, `drupal_strtoupper`, `drupal_substr`, `drupal_ucfirst`, `decode_entities`, `parse_size`, `drupal_is_cli`, `drupal_http_build_query`, `drupal_get_query_parameters`, `format_plural`, `format_size`, `format_interval`, `format_date`, `arg`, `drupal_get_destination`, `user_load`, `user_roles`, `node_access`, `entity_label`, `taxonomy_term_load`, `file_load`, `file_create_url`, `drupal_get_token` and `drupal_valid_token`.

`legacy-removed.php` defines functions that Drupal 8 through 10 core removed and that code written for those releases still calls:

`watchdog_exception`, `module_load_include`, `user_role_names`, `file_icon_class`, `user_password`, `debug`, `file_save_data`, `file_munge_filename`, `drupal_get_module_schema` and `drupal_get_installed_schema_version`.

`node_access()` answers false for an operation it cannot translate. Every definition is guarded, so a function that core defines wins.
