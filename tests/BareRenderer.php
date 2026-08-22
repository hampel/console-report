<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Hampel\ConsoleReport\RendersDetails;

/**
 * The renderers with no console framework underneath them at all.
 *
 * Not a command, not a Symfony one and not a Laravel one - the traits do not ask for a
 * base class, only for somewhere to write, and this is the class that says so. It is also
 * a third context for PHPStan to analyse the traits through, which is where the check
 * that they reference nothing a framework provides actually happens.
 */
class BareRenderer
{
    use RendersDetails;

    public function show(string $label, string $value): void
    {
        $this->detail($label, $value);
    }
}
