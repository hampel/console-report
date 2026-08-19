<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport;

use Symfony\Component\Console\Command\Command;

/**
 * Marker-label-detail rows, for a command that exercises an installation and reports on
 * what it found: `[ ok ]`, `[warn]`, `[fail]`, and a blank marker for a check that did
 * not apply.
 *
 * The four outcomes are the vocabulary worth keeping identical between tools, because
 * the person reading them is reading them mid-incident. A warning is something to know
 * about that does not stop the tool working; a skip is a check that had nothing to say,
 * which is not the same as one that passed.
 *
 * The tallies are kept so that the command can exit non-zero without counting for
 * itself, which is what makes it usable as a post-deploy check.
 *
 * Expects the using class to be an Illuminate\Console\Command.
 */
trait RendersChecks
{
    /**
     * Width of the label column, wide enough for the longest label in the run.
     */
    protected int $checkLabelWidth = 24;

    protected bool $checkFailed = false;

    protected bool $checkWarned = false;

    protected function checkOk(string $label, string $detail = ''): void
    {
        $this->checkResult('<info>[ ok ]</info>', $label, $detail);
    }

    protected function checkWarn(string $label, string $detail = ''): void
    {
        $this->checkWarned = true;

        $this->checkResult('<comment>[warn]</comment>', $label, $detail);
    }

    protected function checkFail(string $label, string $detail = ''): void
    {
        $this->checkFailed = true;

        $this->checkResult('<error>[fail]</error>', $label, $detail);
    }

    /**
     * A check that did not apply - an optional binary that is not installed, a section
     * with nothing configured to check.
     */
    protected function checkSkip(string $label, string $detail = ''): void
    {
        $this->checkResult('[    ]', $label, $detail);
    }

    protected function checkResult(string $marker, string $label, string $detail): void
    {
        // mb_str_pad, not sprintf's %-Ns: that pads to a byte count, so a label with any
        // multibyte character in it drags the detail column left one column per character
        $this->line(rtrim(sprintf(
            '  %s %s %s',
            $marker,
            mb_str_pad($label, $this->checkLabelWidth),
            $detail
        )));
    }

    protected function checksFailed(): bool
    {
        return $this->checkFailed;
    }

    protected function checksWarned(): bool
    {
        return $this->checkWarned;
    }

    /**
     * Failure if any check failed, success otherwise - a warning is not a failure.
     */
    protected function checkExitCode(): int
    {
        return $this->checkFailed ? Command::FAILURE : Command::SUCCESS;
    }
}
