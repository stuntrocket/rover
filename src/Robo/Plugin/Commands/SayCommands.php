<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Output and messaging commands
 */
class SayCommands extends BaseCommand
{
    /**
     * Display a message
     *
     * @command rover:say
     * @param string $message The message to display
     */
    public function sayMessage(string $message): Result
    {
        $this->say($message);
        return Result::success($this);
    }
}
