<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\RendersDetails;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(RendersDetails::class)]
class RendersDetailsTest extends TestCase
{
    public function testItLeadsFromTheLabelToTheValue(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showDetail('Sites Path', '/etc/wback/wback.toml'));

        $this->assertSame(
            '  Sites Path ' . str_repeat('.', 100 - 2 - 4 - 10 - 21) . ' /etc/wback/wback.toml',
            $this->lines($output)[0]
        );
    }

    public function testItFillsTheWidthLessARightMargin(): void
    {
        // two columns are left at the right, matching the margin at the left
        $output = $this->render(fn (ReportCommand $command) => $command->showDetail('Label', 'value'));

        $this->assertSame(self::WIDTH - 2, mb_strlen($this->lines($output)[0]));
    }

    public function testItRunsAHeadingOutToTheMargin(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showHeading('Backup'));

        $line = $this->lines($output)[0];

        $this->assertSame('  Backup ' . str_repeat('.', 100 - 2 - 3 - 6), $line);
        $this->assertSame(self::WIDTH - 2, mb_strlen($line));
    }

    public function testItWrapsAValueThatWillNotFitRatherThanCuttingItOff(): void
    {
        // a truncated path is worth less than nothing: it still looks like a path
        $path = '/mnt/' . str_repeat('backup-volume/', 8) . 'wback.toml';

        $output = $this->render(fn (ReportCommand $command) => $command->showDetail('Sites Path', $path));

        $this->assertSame(['  Sites Path', '    ' . $path], $this->lines($output));
    }

    public function testItWrapsWithoutAValueLineWhenThereIsNoValue(): void
    {
        $heading = str_repeat('a very long heading ', 8);

        $output = $this->render(fn (ReportCommand $command) => $command->showHeading($heading));

        $this->assertSame(['  ' . $heading], $this->lines($output));
    }

    public function testItMeasuresWhatIsVisibleRatherThanTheMarkup(): void
    {
        // <fg=yellow> and </> occupy no columns, so the line still fills the width
        $output = $this->render(fn (ReportCommand $command) => $command->showDetail('Remote', '<fg=yellow>not set</>'));

        $this->assertSame(self::WIDTH - 2, mb_strlen($this->lines($output)[0]));
    }

    public function testItDoesNotDrawALineWiderThanItCanBeFollowed(): void
    {
        putenv('COLUMNS=400');

        $output = $this->render(fn (ReportCommand $command) => $command->showDetail('Label', 'value'));

        $this->assertSame(148, mb_strlen($this->lines($output)[0]));
    }
}
