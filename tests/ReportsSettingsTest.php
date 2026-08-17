<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\ReportsSettings;
use PHPUnit\Framework\Attributes\CoversTrait;

#[CoversTrait(ReportsSettings::class)]
class ReportsSettingsTest extends TestCase
{
    /**
     * @return array<string, array<string, string>>
     */
    private function settings(): array
    {
        return [
            'Application' => ['Version' => '7.2.0', 'Timezone' => 'UTC'],
            'Migration Source' => ['Migrate User' => 'deploy'],
            'Logging' => ['Default' => 'stack'],
        ];
    }

    public function testItReportsEverySectionInTheOrderGiven(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings()));

        $this->assertSame(
            ['Application', 'Version', 'Timezone', 'Migration Source', 'Migrate User', 'Logging', 'Default'],
            $this->labels($output)
        );
    }

    public function testItReportsOnlyTheSectionAskedFor(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings(), 'logging'));

        $this->assertSame(['Logging', 'Default'], $this->labels($output));
    }

    public function testItReportsSeveralSectionsAtOnce(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings(), 'logging,application'));

        // in the order the settings declare them, not the order they were asked for
        $this->assertSame(['Application', 'Version', 'Timezone', 'Logging', 'Default'], $this->labels($output));
    }

    public function testItMatchesASectionNameLoosely(): void
    {
        foreach (['Migration Source', 'migration_source', ' MIGRATION-SOURCE '] as $only) {
            $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings(), $only));

            $this->assertSame(['Migration Source', 'Migrate User'], $this->labels($output), $only);
        }
    }

    public function testItReportsNothingForASectionThatIsNotThere(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings(), 'nonsense'));

        $this->assertSame([], $this->labels($output));
    }

    public function testItReportsEverythingWhenTheFilterIsEmpty(): void
    {
        $output = $this->render(fn (ReportCommand $command) => $command->showSettings($this->settings(), ''));

        $this->assertCount(7, $this->labels($output));
    }

    public function testItTakesTheFilterFromTheCommandLine(): void
    {
        $output = $this->render(
            fn (ReportCommand $command) => $command->showSettings($this->settings(), $command->onlyOption()),
            ['--only' => 'logging']
        );

        $this->assertSame(['Logging', 'Default'], $this->labels($output));
    }

    /**
     * The labels reported, headings included, in the order they were written.
     *
     * @return list<string>
     */
    private function labels(string $output): array
    {
        $labels = [];

        foreach ($this->lines($output) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $labels[] = trim((string) preg_replace('/\s\.+\s?.*$/', '', $line));
        }

        return $labels;
    }
}
