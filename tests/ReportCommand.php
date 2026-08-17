<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\FormatsValues;
use Hampel\ConsoleReport\RendersChecks;
use Hampel\ConsoleReport\ReportsSettings;
use Illuminate\Console\Command;

/**
 * A command using every trait, driven by a closure the test supplies.
 *
 * The trait methods are protected, as they are meant to be used - so a test reaches them
 * the way an application would, from inside the command that uses them. The passthroughs
 * are named so that none of them collides with a method Illuminate\Console\Command
 * already has: `fail()` and `warn()` are both taken, which is the trap the check methods
 * are prefixed to avoid in the first place.
 */
class ReportCommand extends Command
{
    use FormatsValues;
    use RendersChecks;
    use ReportsSettings;

    /** @var string */
    protected $signature = 'report {--only=}';

    /** @var callable(self): mixed */
    protected $report;

    /**
     * @param callable(self): mixed $report
     */
    public function __construct(callable $report)
    {
        parent::__construct();

        $this->report = $report;
    }

    public function handle(): int
    {
        $code = ($this->report)($this);

        return is_int($code) ? $code : self::SUCCESS;
    }

    /**
     * The --only option, typed - Command::option() is mixed, being whatever the
     * definition allows.
     */
    public function onlyOption(): ?string
    {
        $only = $this->option('only');

        return is_string($only) ? $only : null;
    }

    /**
     * @param array<string, array<string, string>> $sections
     */
    public function showSettings(array $sections, ?string $only = null): void
    {
        $this->reportSettings($sections, $only);
    }

    public function showDetail(string $label, string $value = ''): void
    {
        $this->detail($label, $value);
    }

    public function showHeading(string $heading): void
    {
        $this->heading($heading);
    }

    public function showOk(string $label, string $detail = ''): void
    {
        $this->checkOk($label, $detail);
    }

    public function showWarn(string $label, string $detail = ''): void
    {
        $this->checkWarn($label, $detail);
    }

    public function showFail(string $label, string $detail = ''): void
    {
        $this->checkFail($label, $detail);
    }

    public function showSkip(string $label, string $detail = ''): void
    {
        $this->checkSkip($label, $detail);
    }

    public function formatPath(?string $value): string
    {
        return $this->path($value);
    }

    public function formatRequired(?string $value): string
    {
        return $this->required($value);
    }

    public function formatOptional(?string $value): string
    {
        return $this->optional($value);
    }

    public function formatSecret(?string $value): string
    {
        return $this->secretStatus($value);
    }

    public function formatRedacted(?string $value): string
    {
        return $this->redacted($value);
    }

    public function outcome(): int
    {
        return $this->checkExitCode();
    }

    public function hasWarned(): bool
    {
        return $this->checksWarned();
    }

    public function hasFailed(): bool
    {
        return $this->checksFailed();
    }

    public function useLabelWidth(int $width): void
    {
        $this->checkLabelWidth = $width;
    }
}
