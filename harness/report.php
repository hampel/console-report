<?php

/**
 * Exercise: the output this package exists to produce, to look at.
 *
 * The suite asserts line widths and markup, which is the right thing for it to do and
 * tells you nothing about whether the result reads well - and reading well mid-incident
 * is the entire design brief. This renders the real thing, in colour, so you can judge it.
 *
 * It is also where the README's two output blocks come from. They are quoted at
 * COLUMNS=80 and must be generated rather than trusted, so the first half of this
 * exercise reproduces them exactly; copy from here when they change.
 *
 * Everything is driven through the traits' real protected API - `path()`, `checkOk()` -
 * from inside a command that uses them, which is how a consumer reaches them. The test
 * suite's ReportCommand exposes public passthroughs under different names, so an exercise
 * written against those would demonstrate an API nobody has.
 *
 * @var Hampel\Rig\Io $io
 */

use Hampel\ConsoleReport\FormatsValues;
use Hampel\ConsoleReport\RendersChecks;
use Hampel\ConsoleReport\ReportsSettings;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * A command shaped like one that would use this package, as the README's examples are.
 */
class ExampleCommand extends Command
{
    use FormatsValues;
    use RendersChecks;
    use ReportsSettings;

    /** @var string */
    protected $signature = 'app:report {--only=}';

    private Closure $body;

    public function __construct(Closure $body)
    {
        parent::__construct();

        $this->body = $body;
    }

    /**
     * Bound to $this, so an exercise reaches the traits' protected methods the way a
     * consumer does - by writing `$this->path(...)` in its own handle().
     */
    public function handle(): int
    {
        $code = $this->body->call($this);

        return is_int($code) ? $code : self::SUCCESS;
    }
}

/**
 * The little of a framework a console command asks for: it dispatches handle() through
 * the container and asks whether it is under test, to decide whether prompts may fall
 * back to interactive input. This is not a test, so it says so.
 */
$container = new class () extends Container {
    public function runningUnitTests(): bool
    {
        return false;
    }
};

/**
 * Run a command body and let it write straight to the terminal, so the markup the traits
 * emit arrives as real colour rather than as the tags the suite asserts on.
 *
 * @param array<string, string> $input
 */
$run = function (Closure $body, array $input = []) use ($io, $container): int {
    $command = new ExampleCommand($body);
    $command->setLaravel($container);

    $arguments = new ArrayInput($input);
    $output = new ConsoleOutput();
    $output->setDecorated($io->isDecorated());

    return $command->run($arguments, new OutputStyle($arguments, $output));
};

$columns = getenv('COLUMNS');

$io->title('console-report · report');

// ---------------------------------------------------------------------------------
// The README blocks. Pinned to 80 columns, because a block whose width depends on the
// terminal it was captured in is a block that stops matching the next time anyone looks.
// ---------------------------------------------------------------------------------

putenv('COLUMNS=80');

$io->info('  the settings dump, at COLUMNS=80 - the README block');
$io->line();

$run(function () {
    $this->reportSettings([
    'Application' => [
        'Version' => '7.2.0',
        'Environment File' => $this->path('/etc/backup/.env'),
    ],

    'Backup' => [
        'Targets Path' => 'targets.toml <fg=yellow>(relative to /srv/backup)</>',
        'Cloud Remote' => $this->required(null),
        'Extra Options' => $this->redacted('--password=hunter2 --quick'),
        'API Token' => $this->secretStatus(str_repeat('t', 40)),
    ],
    ], $this->option('only'));
});

$io->line();
$io->info('  the check rows - the README block');
$io->line();

$run(function () {
    $this->checkOk('rclone', '/usr/bin/rclone v1.60.1');
    $this->checkWarn('cloud remote', 'does not exist yet - it will be created on first transfer');
    $this->checkFail('mysqldump', 'not found on PATH');
    $this->checkSkip('sync', 'nothing configured');
});

// ---------------------------------------------------------------------------------
// Everything the README has no room for, at whatever width you are actually reading at.
// ---------------------------------------------------------------------------------

putenv($columns === false ? 'COLUMNS' : 'COLUMNS=' . $columns);

$io->line();
$io->info('  the rest, at your terminal width - resize and run it again');
$io->line();

$run(function () {
    $this->reportSettings([
    'Formatters' => [
        'path, absolute' => $this->path('/srv/backup/targets.toml'),
        'path, relative' => $this->path('targets.toml'),
        'path, phar URI' => $this->path('phar:///opt/bin/sites'),
        'path, unset' => $this->path(null),
        'required' => $this->required('a value the tool needs'),
        'required, unset' => $this->required(''),
        'optional' => $this->optional('a value it usually has'),
        'optional, unset' => $this->optional(null),
        'secretStatus' => $this->secretStatus(str_repeat('s', 64)),
        'secretStatus, unset' => $this->secretStatus(null),
        'redacted, long flag' => $this->redacted('--verbose --api-token=hunter2'),
        'redacted, short flag' => $this->redacted('-u backup -phunter2'),
    ],

    'Wrapping' => [
        'A value that fits' => '/var/log/backup.log',
        'A value that does not' => '/var/lib/backup/spool/2026-08-20T09:14:22Z/incremental/manifest.json',
        'A heading has no value, so its dots run to the margin' => '',
    ],
    ]);
});

$io->line();
$io->info('  --only=formatters - matched loosely, so hyphens and case do not matter');
$io->line();

$run(function () {
    $this->reportSettings([
    'Formatters' => ['Kept' => 'this section was asked for'],
    'Wrapping' => ['Dropped' => 'this one was not'],
    ], $this->option('only'));
}, ['--only' => 'formatters']);

$io->line();
$io->info('  checkExitCode - a warning is not a failure, which is what makes this usable as a gate');

$outcomes = [
    'every check passed' => function () {
        $this->checkOk('rclone', 'found');

        return $this->checkExitCode();
    },
    'one warned' => function () {
        $this->checkWarn('cloud remote', 'will be created on first transfer');

        return $this->checkExitCode();
    },
    'one failed' => function () {
        $this->checkFail('mysqldump', 'not found on PATH');

        return $this->checkExitCode();
    },
];

foreach ($outcomes as $description => $outcome) {
    $io->line();
    $io->info('  ' . $description);
    $io->value('exit code', $run($outcome));
}

$io->line();
$io->value('php', PHP_VERSION);
$io->value('illuminate/console', \Composer\InstalledVersions::getPrettyVersion('illuminate/console'));
$io->value('symfony/console', \Composer\InstalledVersions::getPrettyVersion('symfony/console'));
$io->value('decorated', $io->isDecorated());
