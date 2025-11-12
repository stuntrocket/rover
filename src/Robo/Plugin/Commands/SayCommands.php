<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

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
    public function sayMessage(string $message): Result|ResultData
    {
        $this->say($message);
        return new ResultData(0, "");
    }
}
