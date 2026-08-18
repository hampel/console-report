<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\FormatsValues;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversTrait(FormatsValues::class)]
class FormatsValuesTest extends TestCase
{
    public function testItPrintsAnAbsolutePathAsItIs(): void
    {
        $this->assertSame(
            '/etc/backup/sites.toml',
            $this->format(fn (ReportCommand $command) => $command->formatPath('/etc/backup/sites.toml'))
        );
    }

    public function testItSaysWhatARelativePathResolvesAgainst(): void
    {
        // the same setting names different files depending on where it was run from
        $this->assertSame(
            'sites.toml <fg=yellow>(relative to ' . getcwd() . ')</>',
            $this->format(fn (ReportCommand $command) => $command->formatPath('sites.toml'))
        );
    }

    #[DataProvider('absolutePaths')]
    public function testItRecognisesTheAbsolutePathsOfEveryPlatform(string $path): void
    {
        $this->assertSame($path, $this->format(fn (ReportCommand $command) => $command->formatPath($path)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function absolutePaths(): array
    {
        return [
            'posix' => ['/var/backups'],
            'windows drive' => ['C:\\backups'],
            'windows drive, forward slashes' => ['C:/backups'],
            'unc' => ['\\\\server\\backups'],
        ];
    }

    public function testItReportsAPathThatIsNotSet(): void
    {
        $this->assertSame('<fg=yellow>not set</>', $this->format(fn (ReportCommand $command) => $command->formatPath(null)));
        $this->assertSame('<fg=yellow>not set</>', $this->format(fn (ReportCommand $command) => $command->formatPath('')));
    }

    public function testItFlagsARequiredSettingThatIsMissing(): void
    {
        $this->assertSame('<fg=yellow>not set</>', $this->format(fn (ReportCommand $command) => $command->formatRequired('')));
        $this->assertSame('cloud:backups', $this->format(fn (ReportCommand $command) => $command->formatRequired('cloud:backups')));
    }

    public function testItLetsAnOptionalSettingBeEmpty(): void
    {
        $this->assertSame('<fg=gray>none</>', $this->format(fn (ReportCommand $command) => $command->formatOptional('')));
        $this->assertSame('--fast', $this->format(fn (ReportCommand $command) => $command->formatOptional('--fast')));
    }

    public function testItReportsASecretWithoutPrintingIt(): void
    {
        $token = str_repeat('a', 40);

        $reported = $this->format(fn (ReportCommand $command) => $command->formatSecret($token));

        $this->assertSame('<fg=green>set</> (40 characters)', $reported);
        $this->assertStringNotContainsString($token, $reported);
    }

    public function testItReportsASecretThatIsMissing(): void
    {
        $this->assertSame('<fg=yellow>not set</>', $this->format(fn (ReportCommand $command) => $command->formatSecret(null)));
    }

    #[DataProvider('credentials')]
    public function testItKeepsAPasswordOutOfTheOptionsItReports(string $options): void
    {
        $reported = $this->format(fn (ReportCommand $command) => $command->formatRedacted($options));

        $this->assertStringNotContainsString('hunter2', $reported);
        $this->assertStringContainsString('redacted', $reported);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function credentials(): array
    {
        return [
            'long form' => ['--password=hunter2'],
            'long form, separated' => ['--password hunter2'],
            'short form' => ['-phunter2'],
            'among others' => ['--skip-comments --password=hunter2 --quick'],
            'another tool\'s spelling' => ['--sftp-pass=hunter2'],
            'a token' => ['--api-token=hunter2'],
            'a secret' => ['--client-secret hunter2'],
        ];
    }

    public function testItLeavesTheOptionsThatAreNotSecretsReadable(): void
    {
        $options = '--single-transaction --max-allowed-packet=512M';

        $this->assertSame($options, $this->format(fn (ReportCommand $command) => $command->formatRedacted($options)));
    }

    public function testItReportsNoOptionsAsNone(): void
    {
        $this->assertSame('<fg=gray>none</>', $this->format(fn (ReportCommand $command) => $command->formatRedacted(null)));
    }

    /**
     * @param callable(ReportCommand): string $format
     */
    private function format(callable $format): string
    {
        $formatted = '';

        $this->render(function (ReportCommand $command) use ($format, &$formatted) {
            $formatted = $format($command);
        });

        return $formatted;
    }
}
