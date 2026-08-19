Console Report
==============

[![Tests](https://github.com/hampel/console-report/actions/workflows/tests.yml/badge.svg)](https://github.com/hampel/console-report/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/hampel/console-report.svg?style=flat-square)](https://packagist.org/packages/hampel/console-report)
[![Total Downloads](https://img.shields.io/packagist/dt/hampel/console-report.svg?style=flat-square)](https://packagist.org/packages/hampel/console-report)
[![Open Issues](https://img.shields.io/github/issues-raw/hampel/console-report.svg?style=flat-square)](https://github.com/hampel/console-report/issues)
[![License](https://img.shields.io/packagist/l/hampel/console-report.svg?style=flat-square)](https://packagist.org/packages/hampel/console-report)

Console output for CLI tools that report on themselves — the settings dump that says how a tool is
configured, and the validation run that says whether it works.

By [Simon Hampel](mailto:simon@hampelgroup.com)

Both kinds of output are read by someone who is either installing a tool or in the middle of an
incident with it, which sets the whole standard: **a value is either shown as it is or said to be
missing, and it is never quietly rewritten**.

Installation
------------

`composer require hampel/console-report`

Requires PHP 8.3 or later, and Laravel 12 or 13 (`illuminate/console`). Works the same in
[Laravel Zero](https://laravel-zero.com), which is what it was written for.

Why not `twoColumnDetail`
-------------------------

Laravel ships this shape already, as `$this->components->twoColumnDetail()`, and it cannot be used
for anything holding a path. `render()` puts both of its columns through a fixed mutator chain
including `EnsureRelativePaths`, which does a blunt `str_replace(base_path().'/', '', $string)` on
every value, with no way to opt out:

```
  Environment File ...................................................... .env
  Targets Path .......................................... storage/targets.toml
  Backup Disk ................................................. storage/backup
```

Every one of those is an absolute path with the application directory removed. For `artisan about`
that is a tidy touch. For a settings dump it is the one mutation you cannot afford — *which file is
this actually reading* is the entire question being asked, and those read as plausible relative
paths, so nothing looks wrong. Worse, a path outside the application directory is left alone, so the
output is inconsistent with no hint that anything was touched.

Reporting settings
------------------

`ReportsSettings` takes sections of label-value pairs and renders them, optionally filtered to the
sections named on the command line:

```php
use Hampel\ConsoleReport\FormatsValues;
use Hampel\ConsoleReport\ReportsSettings;
use LaravelZero\Framework\Commands\Command;

class Config extends Command
{
    use FormatsValues;
    use ReportsSettings;

    protected $signature = 'app:config {--only= : The sections to display, comma separated}';

    public function handle(): int
    {
        $this->reportSettings([
            'Application' => [
                'Version' => $this->app->version(),
                'Environment File' => $this->path($this->laravel->environmentFilePath()),
            ],

            'Backup' => [
                'Targets Path' => $this->path(config('backup.targets_path')),
                'Cloud Remote' => $this->required(config('backup.cloud_remote')),
                'Extra Options' => $this->redacted(config('backup.options')),
                'API Token' => $this->secretStatus(config('backup.token')),
            ],
        ], $this->option('only'));

        return self::SUCCESS;
    }
}
```

```
  Application ................................................................
  Version .............................................................. 7.2.0
  Environment File .......................................... /etc/backup/.env

  Backup .....................................................................
  Targets Path ........................ targets.toml (relative to /srv/backup)
  Cloud Remote ....................................................... not set
  Extra Options .................................. --password=redacted --quick
  API Token .............................................. set (40 characters)
```

The content stays with your application. What a tool's settings dump should contain is an editorial
decision — and the value of one is that it can be trusted to be complete, so a setting missing from
it reads as a setting the tool does not have.

### The value formatters

| method | for | reports |
|---|---|---|
| `path()` | a filesystem path | absolute as-is; relative with what it resolves against; `not set` when empty |
| `required()` | a setting the tool needs | the value, or `not set` |
| `optional()` | a setting usually left empty | the value, or `none` |
| `secretStatus()` | a credential | `set (40 characters)` — never the value |
| `redacted()` | free-form options passed to a binary | the options, with anything password-shaped replaced |

`path()` says what a relative path resolves against because it resolves against the process working
directory — which under cron is wherever the crontab last changed to — so the same setting names
different files depending on where the command was run from.

`redacted()` covers the flag spellings people actually use (`--password=`, `-p`, and long options
whose names contain `pass`, `secret` or `token`) rather than every conceivable one. Credentials
belong in a defaults file the binary reads for itself; this is a backstop for when they are not.

Reporting checks
----------------

`RendersChecks` is the counterpart for a command that exercises an installation rather than
describing it — the one you run after provisioning a server, and the first one you run when
something has gone quiet:

```php
use Hampel\ConsoleReport\RendersChecks;

class Validate extends Command
{
    use RendersChecks;

    public function handle(): int
    {
        $this->checkOk('rclone', '/usr/bin/rclone v1.60.1');
        $this->checkWarn('cloud remote', 'does not exist yet - it will be created on first transfer');
        $this->checkFail('mysqldump', 'not found on PATH');
        $this->checkSkip('sync', 'nothing configured');

        return $this->checkExitCode();
    }
}
```

```
  [ ok ] rclone                   /usr/bin/rclone v1.60.1
  [warn] cloud remote             does not exist yet - it will be created on first transfer
  [fail] mysqldump                not found on PATH
  [    ] sync                     nothing configured
```

Four outcomes, because the distinctions matter to someone reading this mid-incident: a **warning**
is something to know about that does not stop the tool working, and a **skip** is a check that had
nothing to say, which is not the same as one that passed.

`checkExitCode()` returns failure if any check failed and success otherwise — a warning is not a
failure — which is what makes the command usable as a post-deploy check. `checksFailed()` and
`checksWarned()` are there if you want to say something else at the end. Set `$checkLabelWidth` to
line the detail column up with the longest label in your run.

Drawing the lines
-----------------

`RendersDetails` is the primitive underneath `ReportsSettings`, if you want the dotted line
somewhere else: `detail($label, $value)` and `heading($section)`.

Lines fill the terminal width, up to `$detailMaxWidth` (150 by default) — beyond that the eye cannot
follow a row of dots back to its label. A value too long to fit **wraps to its own line rather than
being truncated**, because a truncated path is worth less than nothing: it still looks like a path,
and it is not one. Console markup is not counted when measuring, so a coloured value still lines up.

License
-------

MIT. See [LICENSE.md](LICENSE.md).
