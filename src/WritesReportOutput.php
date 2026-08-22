<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport;

use LogicException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The one seam between the renderers and the command they live in.
 *
 * Everything else here is framework-neutral, and this is why: a renderer needs somewhere
 * to write, and the console frameworks disagree about where that is. Symfony hands
 * `execute()` an OutputInterface, Laravel keeps one on the command and wraps it in
 * `line()`, and XenForo builds a SymfonyStyle inside each command. Asking for the output
 * outright is the only thing all three can answer.
 *
 * `writeln()` is the whole of the requirement, so anything implementing OutputInterface
 * works - including a BufferedOutput in a test and a SymfonyStyle in an add-on.
 */
trait WritesReportOutput
{
    private ?OutputInterface $reportOutput = null;

    /**
     * Give the renderers somewhere to write, before asking them to render anything.
     */
    public function setReportOutput(OutputInterface $output): void
    {
        $this->reportOutput = $output;
    }

    protected function reportOutput(): OutputInterface
    {
        if ($this->reportOutput === null) {
            throw new LogicException(
                'No output to report to. Call setReportOutput($output) before rendering - '
                . 'from execute() in a Symfony or XenForo command, or from handle() in a '
                . 'Laravel one, where it is setReportOutput($this->getOutput()).'
            );
        }

        return $this->reportOutput;
    }

    /**
     * One line, or a blank one. Deliberately the only way this package writes anything.
     */
    protected function reportLine(string $line = ''): void
    {
        $this->reportOutput()->writeln($line);
    }
}
