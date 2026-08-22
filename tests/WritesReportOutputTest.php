<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\WritesReportOutput;
use LogicException;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversTrait(WritesReportOutput::class)]
class WritesReportOutputTest extends BaseTestCase
{
    public function testItSaysWhatToDoWhenNothingHasBeenGivenSomewhereToWrite(): void
    {
        $renderer = new BareRenderer();

        $this->expectException(LogicException::class);
        // the message is the whole value of throwing here rather than letting a null
        // deref happen further down - it has to name the call that was missed
        $this->expectExceptionMessageMatches('/setReportOutput/');

        $renderer->show('Archive', '/srv/backup');
    }

    public function testItWritesToWhateverOutputItIsGiven(): void
    {
        $buffer = new BufferedOutput();

        $renderer = new BareRenderer();
        $renderer->setReportOutput($buffer);
        $renderer->show('Archive', '/srv/backup');

        self::assertStringContainsString('Archive', $buffer->fetch());
    }

    public function testItAcceptsANewOutputAfterwards(): void
    {
        // a long-running command that renders into more than one place, and the reason
        // setReportOutput() is not a constructor argument
        $first = new BufferedOutput();
        $second = new BufferedOutput();

        $renderer = new BareRenderer();
        $renderer->setReportOutput($first);
        $renderer->show('First', 'one');
        $renderer->setReportOutput($second);
        $renderer->show('Second', 'two');

        self::assertStringContainsString('First', $first->fetch());
        self::assertStringNotContainsString('First', $second->fetch());
    }
}
