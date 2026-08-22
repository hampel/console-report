<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\RendersChecks;
use Hampel\ConsoleReport\RendersDetails;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\Attributes\CoversTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The same report, rendered from an Illuminate\Console\Command instead of a Symfony one.
 *
 * The rest of the suite drives plain Symfony, which is all the package requires. Laravel
 * is the other console framework these traits are used from, and the thing worth pinning
 * is that it is not a second code path: an OutputStyle is an OutputInterface, so a
 * Laravel command hands over its own output and gets byte-identical lines back.
 *
 * Skipped rather than failed when illuminate/console is absent, because it is a dev
 * dependency now and CI has a corner that removes it to test against Symfony Console 5.4.
 */
#[CoversTrait(RendersChecks::class)]
#[CoversTrait(RendersDetails::class)]
class LaravelIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        if (! class_exists(Command::class)) {
            self::markTestSkipped('illuminate/console is not installed');
        }

        parent::setUp();
    }

    public function testALaravelCommandRendersWhatASymfonyCommandDoes(): void
    {
        $report = function (LaravelReportCommand|ReportCommand $command): void {
            $command->showSettings([
                'Storage' => [
                    'Archive' => $command->formatPath('/srv/backup'),
                    'Token' => $command->formatSecret(str_repeat('t', 12)),
                ],
            ]);

            $command->showOk('rclone', 'found');
            $command->showFail('mysqldump', 'not on PATH');
        };

        self::assertSame($this->render($report), $this->renderThroughLaravel($report));
    }

    public function testALaravelCommandReturnsTheSameExitCode(): void
    {
        $report = function (LaravelReportCommand|ReportCommand $command): int {
            $command->showWarn('cloud remote', 'will be created on first transfer');

            return $command->outcome();
        };

        self::assertSame(Command::SUCCESS, $this->runReport($report));
        self::assertSame(Command::SUCCESS, $this->runThroughLaravel($report)['code']);
    }

    /**
     * @param callable(LaravelReportCommand): mixed $report
     */
    private function renderThroughLaravel(callable $report): string
    {
        return $this->runThroughLaravel($report)['output'];
    }

    /**
     * @param callable(LaravelReportCommand): mixed $report
     * @return array{code: int, output: string}
     */
    private function runThroughLaravel(callable $report): array
    {
        $command = new LaravelReportCommand($report);
        $command->setLaravel(new TestContainer());

        $arguments = new ArrayInput([]);
        $buffer = new BufferedOutput();

        $code = $command->run($arguments, new OutputStyle($arguments, $buffer));

        return ['code' => $code, 'output' => $buffer->fetch()];
    }
}
