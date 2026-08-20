CHANGELOG
=========

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
