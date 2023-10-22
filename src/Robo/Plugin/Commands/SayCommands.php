<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Symfony\ConsoleIO;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class SayCommands extends \Robo\Tasks
{
    /**
     * @command rover:say
     */
    public function say($words)
    {
        $this->say($words);
    }
}
