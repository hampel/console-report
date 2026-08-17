<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\RendersChecks;
use PHPUnit\Framework\Attributes\CoversTrait;
use Symfony\Component\Console\Command\Command;

#[CoversTrait(RendersChecks::class)]
class RendersChecksTest extends TestCase
{
    public function testItMarksEachOutcomeDistinctly(): void
    {
        $output = $this->render(function (ReportCommand $command) {
            $command->showOk('rclone', '/usr/bin/rclone');
            $command->showWarn('log stack', 'the null channel discards everything');
            $command->showFail('mysqldump', 'not found');
            $command->showSkip('sync', 'nothing configured');
        });

        $this->assertSame([
            '  [ ok ] rclone                   /usr/bin/rclone',
            '  [warn] log stack                the null channel discards everything',
            '  [fail] mysqldump                not found',
            '  [    ] sync                     nothing configured',
        ], $this->lines($output));
    }

    public function testItLinesUpTheDetailColumnAtAWidthTheCommandChooses(): void
    {
        $output = $this->render(function (ReportCommand $command) {
            $command->useLabelWidth(10);
            $command->showOk('zip', '/usr/bin/zip');
        });

        $this->assertSame('  [ ok ] zip        /usr/bin/zip', $this->lines($output)[0]);
    }

    public function testItLeavesNoTrailingSpaceOnACheckWithNoDetail(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showOk('lock'));

        $this->assertSame('  [ ok ] lock', $this->lines($output)[0]);
    }

    public function testItFailsTheRunWhenACheckFailed(): void
    {
        $code = $this->runReport(function (ReportCommand $command) {
            $command->showOk('rclone');
            $command->showFail('mysqldump', 'not found');

            return $command->outcome();
        });

        $this->assertSame(Command::FAILURE, $code);
    }

    public function testItDoesNotFailTheRunForAWarning(): void
    {
        // a warning is something to know about, not a reason to fail a deployment check
        $code = $this->runReport(function (ReportCommand $command) {
            $command->showWarn('remote', 'does not exist yet');
            $command->showSkip('sync', 'nothing configured');

            return $command->outcome();
        });

        $this->assertSame(Command::SUCCESS, $code);
    }

    public function testItRemembersWhatItSaw(): void
    {
        $this->runReport(function (ReportCommand $command) {
            $this->assertFalse($command->hasWarned());
            $this->assertFalse($command->hasFailed());

            $command->showWarn('remote', 'does not exist yet');

            $this->assertTrue($command->hasWarned());
            $this->assertFalse($command->hasFailed());

            $command->showFail('mysqldump', 'not found');

            $this->assertTrue($command->hasFailed());

            return Command::SUCCESS;
        });
    }
}
