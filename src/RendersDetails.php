<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport;

use Symfony\Component\Console\Terminal;

/**
 * Label-dots-value lines, for a command reporting on the state of something.
 *
 * Laravel ships this shape as `$this->components->twoColumnDetail()`, and it cannot be
 * used for anything holding a path: `render()` runs both of its columns through a fixed
 * mutator chain that includes EnsureRelativePaths, which does a blunt
 * `str_replace(base_path().'/', '', $string)` on every value with no way to opt out. An
 * absolute path under the application directory comes out looking like a relative one -
 * the environment file as `.env`, a storage directory as `storage/backup` - which is the
 * one mutation a settings dump cannot afford, since "which file is this actually
 * reading" is the question being asked.
 *
 * Expects the using class to be an Illuminate\Console\Command.
 */
trait RendersDetails
{
    /**
     * Widest line to draw, however wide the terminal is.
     *
     * Beyond this the eye cannot follow a row of dots back to its label.
     */
    protected int $detailMaxWidth = 150;

    /**
     * One dotted line, or two when the value will not fit beside its label.
     *
     * Wrapping rather than truncating: a path is worth less than nothing when it is cut
     * off, because it still looks like a path and is not one.
     */
    protected function detail(string $label, string $value = ''): void
    {
        $width = $this->detailWidth();

        // two margin columns, a space either side of the leader, and - for a heading,
        // whose value is empty - no trailing space to strip later
        $spacing = $value === '' ? 3 : 4;
        $room = $width - 2 - $spacing - $this->visibleLength($label) - $this->visibleLength($value);

        if ($room < 2) {
            $this->line("  {$label}");

            if ($value !== '') {
                $this->line("    {$value}");
            }

            return;
        }

        $line = "  {$label} <fg=gray>" . str_repeat('.', $room) . '</>';

        $this->line($value === '' ? $line : "{$line} {$value}");
    }

    /**
     * A section heading: the same line with no value, so the dots run to the margin.
     */
    protected function heading(string $heading): void
    {
        $this->detail("<fg=green;options=bold>{$heading}</>");
    }

    protected function detailWidth(): int
    {
        return min((new Terminal())->getWidth(), $this->detailMaxWidth);
    }

    /**
     * The visible width of a string, ignoring the console's own markup.
     */
    protected function visibleLength(string $value): int
    {
        return mb_strlen((string) preg_replace('/<[^>]+>/', '', $value));
    }
}
