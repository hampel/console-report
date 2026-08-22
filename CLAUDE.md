# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`hampel/console-report` — a Composer library published on Packagist. Five traits that a console
command uses to report on itself: a settings dump (`ReportsSettings` over `RendersDetails`, with
`FormatsValues` for the values), a validation run (`RendersChecks`), and `WritesReportOutput`
underneath both of the renderers.

There is no service provider, no facade, no configuration, and nothing is bound into a container.
**The only thing the traits need from the using class is an `OutputInterface`, handed over with
`setReportOutput()`** — so the requirement is Symfony Console and nothing more. Laravel, Laravel
Zero, plain Symfony and XenForo add-on commands are all just consumers of that.

## Commands

```bash
composer install
vendor/bin/phpunit                              # full suite
vendor/bin/phpunit --filter testItWrapsAValue   # single test
vendor/bin/phpunit tests/FormatsValuesTest.php  # single file
php8.5 vendor/bin/phpunit                       # ceiling of the supported range
```

`composer check` runs Pint, PHPStan and the suite — the same three things CI runs, so a green check
locally means a green build. Individually: `composer lint` (Pint, no writes), `composer format`
(Pint, writing), `composer analyse`, `composer test`.

## Looking at the output

```bash
vendor/bin/rig report                 # render the real thing, in colour
vendor/bin/rig report --php=php8.5    # the same, at the ceiling of the range
```

`harness/report.php` drives the traits from inside a command that uses them, the way a consumer
does, and writes to the terminal rather than to a buffer. It is not a test — it asserts nothing and
returns no verdict — and it is not run by CI. It is there because whether output *reads well* is the
whole design brief and is not a thing the suite can tell you: `BufferedOutput` is undecorated, so
every assertion in `tests/` sees `<fg=yellow>` as a literal tag and never as a colour.

**The README's two output blocks come from here.** They are quoted at `COLUMNS=80`, which the first
half of the exercise pins, so regenerate rather than hand-edit them — a pasted block gets adjusted
when its values change and quietly stops matching what the renderer emits.

## Testing

`tests/TestCase.php` runs a real `Symfony\Component\Console\Command` and hands back what it wrote.
`ReportCommand` is that command: it uses every trait and exposes each one through a public
passthrough, so the tests reach them the way an application would — from inside a command. The whole
suite runs against plain Symfony Console, which is what the package asks for.

**Three contexts, deliberately.** PHPStan analyses a trait only through a class that uses one, so the
three in `tests/` are what the level-10 run actually checks:

| class | what it pins |
|---|---|
| `ReportCommand` | plain Symfony Console — the package's own requirement |
| `LaravelReportCommand` | `Illuminate\Console\Command`, a dev dependency now |
| `BareRenderer` | no console framework at all, only `setReportOutput()` |

**No Testbench.** These traits read no configuration and touch no filesystem. `TestContainer` exists
only for `LaravelReportCommand` — `Illuminate\Console\Command` dispatches `handle()` through a
container and `ConfiguresPrompts` asks it `runningUnitTests()`. If a future trait needs config or
storage, that is the point to reach for Testbench rather than to keep extending the stub.

**`LaravelIntegrationTest` skips itself when `illuminate/console` is absent**, which is not defensive
padding: one CI corner removes it so that the floor of the `symfony/console` range can be resolved at
all. `Skipped: 2` in that job's log is the evidence the corner did what it claims.

**No larastan either.** It bootstraps a Laravel application to read `LARAVEL_VERSION` and there is
none here, so PHPStan aborts before analysing anything. It also wants `illuminate/database` and 26
other packages in order to reason about Eloquent and facades, none of which this package uses —
`src` imports only `Symfony\Component\Console\Terminal`, Symfony's `Command` and `OutputInterface`.
That list is worth re-checking after any change to `src`: it is the shortest statement of what this
package actually depends on, and `composer.json` should agree with it.

Two things to know before adding tests:

- **Terminal width is an input.** `TestCase` pins `COLUMNS` to 100 so line lengths are assertable,
  and restores whatever was there. Symfony's `Terminal` reads the variable on every call, so a test
  can change it mid-test (see `testItDoesNotDrawALineWiderThanItCanBeFollowed`).
- **A rendered line is two columns short of the terminal width.** Two margin columns on the left and
  a matching two on the right, which is why the assertions read `self::WIDTH - 2`.

## Architecture

**Traits, not a base class.** Consumers already extend something — `LaravelZero\Framework\Commands\Command`,
`XF\Cli\Command\AbstractCommand`, `Symfony\Component\Console\Command\Command` — and a base class here
would take that away from them. It also lets a command use only the parts it wants — a validation
command has no use for the settings machinery.

**One seam, and it is an `OutputInterface`.** The console frameworks disagree about where a command's
output lives: Symfony passes it into `execute()`, Laravel keeps it on the command behind `line()`,
XenForo builds a `SymfonyStyle` inside each command. Asking for the output outright is the only
question all three can answer, and `writeln()` is the whole of what the renderers need. That is why
`setReportOutput()` is a call the consumer makes rather than something sniffed for — a sniff would
work, and it was measured: it costs `treatPhpDocTypesAsCertain: false` to stay level-10 clean,
because Laravel's `getOutput()` is typed only in PHPDoc.

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

**The four check outcomes are a contract, not a palette.** `[ ok ]`, `[warn]`, `[fail]` and the
blank marker mean the same thing in every tool that uses this, because the person reading them is
reading them mid-incident: a warning does not stop the tool working, and a skip is a check that had
nothing to say rather than one that passed. `checkExitCode()` follows from that — only a failure
exits non-zero — which is what makes a validation command usable as a post-deploy gate. Adding a
fifth outcome, or making a warning exit non-zero, changes behaviour in every consumer at once.

## Version support

PHP 8.3 through 8.5, and `symfony/console` `^5.4|^6.0|^7.0|^8.0` — the only runtime dependency.
Both are deliberate policy rather than whatever happened to resolve: `composer.json` is the source of
truth, and neither constraint should be widened or narrowed casually, since dropping a supported
version after release is a major.

**The 5.4 floor is there for XenForo**, which ships Symfony Console 5.4 in XF 2.3 and cannot use a
package that demands 7.x. `illuminate/console` `^12|^13` is a dev dependency, exercised by
`LaravelIntegrationTest` and by three of the four CI corners.

CI tests the corners of that range rather than the whole matrix — the `symfony/console` floor with
`--prefer-lowest` and no Laravel installed, the Laravel floor with `--prefer-lowest`, the span
ceiling, and the newest platform on the oldest PHP — and PHPStan analyses the whole 8.3-8.5 PHP range
in one pass. Keep `phpstan.neon`'s `phpVersion` and the CI matrix in step with `composer.json`.

**Symfony's own deprecations bound where a corner can run.** Early patches of each Symfony line emit
PHP 8.4 implicit-nullable deprecations, and `phpunit.xml` sets `failOnDeprecation`, so the suite goes
red on them even though they come from `vendor/symfony`. Measured on PHP 8.5: 5.4.34 emits them and
5.4.35 does not; 6.4.0 emits them and 6.4.10 does not; 7.0.0 emits them and 7.1.0 does not. All of
those are clean on PHP 8.3, which is why the `--prefer-lowest` corners run there and only the ceiling
corner runs on 8.5. Widening the constraint downwards again means re-measuring this.

## Conventions

PSR-12 via Pint (`pint.json` sets the preset), `declare(strict_types=1)` in every file, and PHPStan
at level 10 over `src` and `tests` both. Run `composer format` rather than matching the brace style
of code you are pasting in from elsewhere; Pint is the arbiter.

A new dev-only file at the repo root — a tool config, a CI helper — needs an `export-ignore` line in
`.gitattributes`, which is what keeps the Packagist dist archive down to `src`, the README, the
licence and the changelog.
