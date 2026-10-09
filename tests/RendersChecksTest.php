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

    public function testItPutsASectionHeadingAboveTheRowsItIntroduces(): void
    {
        // a blank line then the heading - the same rhythm reportSettings() uses between
        // its own sections, so a command doing both does not change visual language
        $output = $this->render(function (ReportCommand $command) {
            $command->showOk('rclone', 'found');
            $command->showSection('Cloud');
            $command->showOk('remote', 'reachable');
        });

        $this->assertSame([
            '  [ ok ] rclone                   found',
            '',
            '  Cloud',
            '  [ ok ] remote                   reachable',
        ], $this->lines($output));
    }

    public function testItDoesNotRuleUnderASectionHeading(): void
    {
        // a rule measured with strlen() runs one column too far for each multibyte
        // character in the title. There is no rule here, so there is nothing to measure
        // and nothing to get wrong
        $output = $this->render(fn (ReportCommand $command) => $command->showSection('Réseau'));

        $this->assertSame(['', '  Réseau'], $this->lines($output));
    }

    public function testItLinesUpTheDetailColumnAtAWidthTheCommandChooses(): void
    {
        $output = $this->render(function (ReportCommand $command) {
            $command->useLabelWidth(10);
            $command->showOk('zip', '/usr/bin/zip');
        });

        $this->assertSame('  [ ok ] zip        /usr/bin/zip', $this->lines($output)[0]);
    }

    public function testItLinesUpTheDetailColumnForAMultibyteLabel(): void
    {
        // the label column is padded to a visible width, not a byte count
        $output = $this->render(function (ReportCommand $command) {
            $command->showOk('sauvegarde reseau', 'detail');
            $command->showOk('sauvegarde réseau', 'detail');
        });

        [$ascii, $multibyte] = $this->lines($output);

        $this->assertSame(mb_strlen($ascii), mb_strlen($multibyte));
        $this->assertSame(mb_strpos($ascii, 'detail'), mb_strpos($multibyte, 'detail'));
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

    public function testItExitsTwoForAWarningOnlyWhenAskedToBeStrict(): void
    {
        $code = $this->runReport(function (ReportCommand $command) {
            $command->showOk('rclone');
            $command->showWarn('remote', 'does not exist yet');

            return $command->outcome(strict: true);
        });

        $this->assertSame(2, $code);
    }

    public function testItStillExitsOneForAFailureWhenStrict(): void
    {
        // strict adds a third state; it does not renumber the other two, so 1 means
        // failed with the flag and without it
        $code = $this->runReport(function (ReportCommand $command) {
            $command->showWarn('remote', 'does not exist yet');
            $command->showFail('mysqldump', 'not found');

            return $command->outcome(strict: true);
        });

        $this->assertSame(Command::FAILURE, $code);
    }

    public function testItExitsZeroWhenStrictFindsNothingToWarnAbout(): void
    {
        $code = $this->runReport(function (ReportCommand $command) {
            $command->showOk('rclone');
            $command->showSkip('sync', 'nothing configured');

            return $command->outcome(strict: true);
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
