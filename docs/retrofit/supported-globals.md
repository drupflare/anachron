# Supported Globals

Class names are relative to `Drupflare\Anachron\Drupal`.

## `$user`

`User\GlobalUser` lets `global $user` work as it did in Drupal 7. `User\GlobalUserSetter` assigns it on each request, and a PHP process that outlives a request resets it between requests.

## `$language`

`Language\GlobalLanguage` lets `global $language` work as it did in Drupal 7, and `Language\GlobalLanguageSetter` assigns `$language`, `$language_content` and `$language_url`. It resets between requests the same way.
