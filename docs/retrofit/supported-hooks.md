# Supported Hooks

Class names are relative to `Drupflare\Anachron\Drupal`.

## `hook_menu`

Converts the Drupal 7 menu hook into routes and menu link plugins. `Routing\HookMenuRoutes` builds the routes and `Plugin\Derivative\MenuLinkDeriver` builds the menu links.

A normal route uses `Controller\PageCallbackController`. A route whose callback is `drupal_get_form` uses `Controller\DrupalGetFormController`. See [Form API](supported-apis.md#form-api).

Actions and tasks are not supported. `hook_admin_paths_alter` is not supported either: `Routing\HookAdminPaths` stores the paths of routes that carry the `_admin_route` option and never calls the hook.

## Field Hooks

| hook                  | handled by                                                       |
| --------------------- | ---------------------------------------------------------------- |
| `hook_field_info`     | `Plugin\Derivative\FieldItemDeriver::getDerivativeDefinitions()` |
| `hook_field_schema`   | `Plugin\Field\FieldType\FieldItem::schema()`                     |
| `hook_field_is_empty` | `Plugin\Field\FieldType\FieldItem::isEmpty()`                    |
| `hook_field_validate` | `Plugin\Field\FieldType\FieldItemList::validate()`               |

## `hook_permission`

`User\HookPermissions` decorates the permission provider and invokes the hook.

## Block Hooks

| hook                   | handled by                                                   |
| ---------------------- | ------------------------------------------------------------ |
| `hook_block_info`      | `Plugin\Derivative\BlockDeriver::getDerivativeDefinitions()` |
| `hook_block_view`      | `Plugin\Block\Block::build()`                                |
| `hook_block_configure` | `Plugin\Block\Block::blockForm()`                            |
| `hook_block_save`      | `Plugin\Block\Block::blockSubmit()`                          |

## `hook_init` and `hook_exit`

Run from `EventSubscriber\HookInit` and `EventSubscriber\HookExit`.
