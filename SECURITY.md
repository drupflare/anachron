# Security Policy

## Reporting a Vulnerability

Use GitHub's private vulnerability reporting on this repository. Do not open a public issue.

Send the module or code that triggers it, the Drupal and PHP versions, which of the two switches were on, and the request or call that shows the problem.

## Supported Versions

Only the latest release receives fixes.

## In Scope

A report is in scope when legacy code gains access it did not have before the layer, or when the layer weakens a check Drupal 11 makes on its own.

- A route or permission that turns from denied to allowed. A Drupal 7 menu item with no access rule must stay denied, and so must an access callback the layer cannot translate. `node_access()` returns false for an operation it cannot map.
- A request-scoped value that survives into the next request in a PHP process that stays alive, such as `$user` or `$language`.
- A legacy function that skips escaping, token validation or an access check that its Drupal 11 equivalent performs.
- A path where `SyntaxRewriter` or a class alias changes what code runs beyond what the documentation says.

## Out of Scope

- A vulnerability in the legacy module itself. The layer runs the code it is given.
- A vulnerability in Drupal core, PHP or a Composer dependency. Report those to the project that owns them.
- Behavior that only occurs with `anachron_ignore_core_requirement` on and is the declared effect of that switch: an extension that declares an older core is enabled.

## Properties Worth Checking

The [Technical Report](TECHNICAL_REPORT.md#security-properties) records what Drupal 7 and Drupal 11 each do about access defaults, password hashes, code execution, stream wrappers, unserialize, SQL and request state. The layer does not provide Drupal 7's PHP input filter, which calls `eval`, and it does not define `create_function()`.
