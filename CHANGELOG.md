CHANGELOG
=========

Unreleased
----------

**Breaking:** every command using these traits must now call `setReportOutput($output)` before
rendering. See "Upgrading from 1.x" in the README.

* dropped `illuminate/console` as a dependency — the package now requires `symfony/console` alone,
  so plain Symfony Console and XenForo add-on commands can use it as well as Laravel and Laravel Zero
* `symfony/console` widened to `^5.4|^6.0|^7.0|^8.0`, covering the 5.4 that XenForo 2.3 ships
* added `WritesReportOutput`, holding the output the renderers write to
* rendered output is unchanged

1.0.1 (2026-08-20)
------------------

* fixed `path()` reporting a stream wrapper URI as relative — inside a built phar,
  `phar:///opt/bin/tool` was printed as being relative to the working directory
* `symfony/console` is now declared as a dependency
* `phpstan/phpstan` requires `^2.1.22`
* added a harness for looking at the rendered output, run with `vendor/bin/rig`

1.0.0 (2026-08-19)
------------------

First release.

* `ReportsSettings` — sections of label-value pairs, filtered by name
* `RendersDetails` — the dotted line underneath it, wrapping rather than truncating
* `FormatsValues` — `path()`, `required()`, `optional()`, `secretStatus()`, `redacted()`
* `RendersChecks` — `[ ok ]`, `[warn]`, `[fail]` and skipped rows, with the exit code that follows
