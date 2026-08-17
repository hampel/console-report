CHANGELOG
=========

1.0.0 (unreleased)
------------------

First release, extracted from the `app:config` and `app:validate` commands of two CLI tools that had
grown the same output by hand — and had grown the same bug, which is what prompted the package.

* `ReportsSettings` — sections of label-value pairs, filtered by name
* `RendersDetails` — the dotted line underneath it, wrapping rather than truncating
* `FormatsValues` — `path()`, `required()`, `optional()`, `secretStatus()`, `redacted()`
* `RendersChecks` — `[ ok ]`, `[warn]`, `[fail]` and skipped rows, with the exit code that follows
