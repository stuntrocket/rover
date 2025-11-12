<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Demo and example commands
 */
class HelloCommands extends BaseCommand
{
    /**
     * Say hello to someone
     *
     * @command rover:hello
     * @param string $name The name to greet
     */
    public function hello(string $name = 'World'): Result
    {
        $this->say("Hello, $name! 👋");
        $this->info('Rover is ready to help with your Laravel projects!');

        return Result::success($this);
    }
}
