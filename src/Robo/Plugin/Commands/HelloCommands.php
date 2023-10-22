<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class HelloCommands extends \Robo\Tasks
{
    /**
     * @command rover:hello
     */
    public function hello(ConsoleIO $io, $world)
    {
        $io->say("Hello, $world");
    }
}
