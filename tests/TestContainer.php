<?php

declare(strict_types=1);

namespace Hampel\ConsoleReport\Tests;

use Illuminate\Container\Container;

/**
 * The little a console command asks of the framework it runs in.
 *
 * Illuminate\Console\Command dispatches handle() through the container and asks it
 * whether it is running under test, to decide whether prompts may fall back to
 * interactive input. Nothing in this package reads configuration or touches a
 * filesystem, so that is the whole surface, and a container with one method on it
 * beats booting an application to find that out.
 */
class TestContainer extends Container
{
    public function runningUnitTests(): bool
    {
        return true;
    }
}
