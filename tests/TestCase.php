<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs a command and hands back what it wrote.
 *
 * A container rather than a whole application: Illuminate\Console\Command dispatches
 * handle() through the container, and that is the only part of a framework these traits
 * touch. Nothing here needs config, so nothing here needs Testbench.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Terminal width the output is rendered at, so line lengths are assertable.
     */
    protected const WIDTH = 100;

    private string|false $columns = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columns = getenv('COLUMNS');

        putenv('COLUMNS=' . self::WIDTH);
    }

    protected function tearDown(): void
    {
        putenv($this->columns === false ? 'COLUMNS' : 'COLUMNS=' . $this->columns);

        parent::tearDown();
    }

    /**
     * @param callable(ReportCommand): mixed $report what the command should report
     * @param array<string, string> $input command line input, eg ['--only' => 'logging']
     * @return string what it wrote, with the console's markup rendered away
     */
    protected function render(callable $report, array $input = []): string
    {
        return $this->execute($report, $input)['output'];
    }

    /**
     * @param callable(ReportCommand): mixed $report what the command should report
     * @param array<string, string> $input command line input
     * @return int the exit code
     */
    protected function runReport(callable $report, array $input = []): int
    {
        return $this->execute($report, $input)['code'];
    }

    /**
     * @param callable(ReportCommand): mixed $report
     * @param array<string, string> $input
     * @return array{code: int, output: string}
     */
    private function execute(callable $report, array $input): array
    {
        $command = new ReportCommand($report);
        $command->setLaravel(new TestContainer());

        $arguments = new ArrayInput($input);
        $buffer = new BufferedOutput();

        $code = $command->run($arguments, new OutputStyle($arguments, $buffer));

        return ['code' => $code, 'output' => $buffer->fetch()];
    }

    /**
     * @return list<string> the lines written, without the trailing blank one
     */
    protected function lines(string $output): array
    {
        return explode("\n", rtrim($output, "\n"));
    }
}
