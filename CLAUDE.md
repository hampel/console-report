# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`hampel/console-report` — a Composer library published on Packagist. Four traits that a console
command uses to report on itself: a settings dump (`ReportsSettings` over `RendersDetails`, with
`FormatsValues` for the values) and a validation run (`RendersChecks`).

There is no service provider, no facade, no configuration, and nothing is bound into a container.
The traits call `$this->line()` and nothing else, so the only requirement on the using class is that
it is an `Illuminate\Console\Command`.

## Commands

```bash
composer install
vendor/bin/phpunit                              # full suite
vendor/bin/phpunit --filter testItWrapsAValue   # single test
vendor/bin/phpunit tests/FormatsValuesTest.php  # single file
php8.5 vendor/bin/phpunit                       # ceiling of the supported range
```

`composer check` runs Pint, PHPStan and the suite — the same three things CI runs, so a green check
locally means a green build.

## Testing

`tests/TestCase.php` runs a real `Illuminate\Console\Command` and hands back what it wrote.
`ReportCommand` is that command: it uses all four traits and exposes each one through a public
passthrough, so the tests reach them the way an application would — from inside a command.

**No Testbench.** These traits read no configuration and touch no filesystem, so the only part of a
framework they need is the container `Command::run()` dispatches `handle()` through, and
`TestContainer` is that container plus the one method `ConfiguresPrompts` asks for
(`runningUnitTests()`). If a future trait needs config or storage, that is the point to reach for
Testbench rather than to keep extending the stub.

Two things to know before adding tests:

- **Terminal width is an input.** `TestCase` pins `COLUMNS` to 100 so line lengths are assertable,
  and restores whatever was there. Symfony's `Terminal` reads the variable on every call, so a test
  can change it mid-test (see `testItDoesNotDrawALineWiderThanItCanBeFollowed`).
- **A rendered line is two columns short of the terminal width.** Two margin columns on the left and
  a matching two on the right, which is why the assertions read `self::WIDTH - 2`.

## Architecture

**Traits, not a base class.** Consumers are Laravel Zero applications whose commands already extend
`LaravelZero\Framework\Commands\Command`; a base class in this package would take that away from
them. It also lets a command use only the parts it wants — a validation command has no use for the
settings machinery.

**Names are prefixed to avoid `Illuminate\Console\Command`.** `fail()`, `warn()`, `secret()` and
`options()` are all public methods on it, and a trait method that collides is a fatal error at class
declaration, not a test failure. Hence `checkFail()`, `checkWarn()`, `secretStatus()` and
`redacted()`. **Check any new method name against the parent before adding it.**

**`RendersDetails` exists because `twoColumnDetail` mangles paths.** `render()` runs both columns
through a fixed mutator chain including `EnsureRelativePaths`, which strips `base_path().'/'` out of
every value with no way to opt out — so an absolute path under the application directory prints as a
convincing relative one. The README explains it at length because that is the reason to choose this
over what the framework already ships.

**Wrap, never truncate.** A path cut off mid-string still looks like a path and is not one, so a
value that will not fit goes on its own line. Nothing here should ever gain a truncating branch.

## Version support

Tier A under `/srv/www/version-support.html`: PHP 8.3+ (the floor for new work) through 8.5, and
`illuminate/console` `^12|^13`. CI tests the three corners of that range rather than the matrix —
platform floor with `--prefer-lowest`, span ceiling, and newest platform on the oldest PHP — and
PHPStan analyses the whole 8.3-8.5 PHP range in one pass. Keep `phpstan.neon`'s `phpVersion` and the
CI matrix in step with the constraints in `composer.json`.

## Conventions

PSR-12 via Pint (`pint.json` sets the preset), `declare(strict_types=1)` in every file, and PHPStan
at level 10 over `src` and `tests` both. This is **not** the Allman-braced style of the `wback`
application the code came from — it was reformatted on the way in, and belongs to the package now.
