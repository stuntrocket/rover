<?php

use Rover\Robo\Plugin\Commands\BaseCommand;
use Robo\Result;

/**
 * Example Plugin Commands
 */
class ExampleCommands extends BaseCommand
{
    /**
     * Say hello with a custom message
     *
     * @command example:hello
     *
     * @param string $name Name to greet (default: World)
     */
    public function hello(string $name = 'World'): Result
    {
        $this->io()->title('Example Plugin - Hello Command');

        $greeting = \Rover\Config\Config::getInstance()->get('plugins.example.greeting', 'Hello');

        $this->io()->success("$greeting, $name!");
        $this->io()->note('This is a custom command from the example plugin');

        // Trigger a custom hook
        $this->triggerHook('example_hello', ['name' => $name]);

        return Result::success($this);
    }

    /**
     * Show example plugin information
     *
     * @command example:info
     */
    public function info(): Result
    {
        $this->io()->title('Example Plugin Information');

        $info = [
            'Plugin demonstrates:',
            '  • Custom command registration',
            '  • Hook system integration',
            '  • Configuration management',
            '  • Laravel project detection',
            '',
            'Available commands:',
            '  • example:hello - Greet someone',
            '  • example:info - Show this information',
            '  • example:demo - Demonstrate Laravel integration',
        ];

        foreach ($info as $line) {
            $this->io()->writeln($line);
        }

        return Result::success($this);
    }

    /**
     * Demonstrate Laravel integration features
     *
     * @command example:demo
     */
    public function demo(): Result
    {
        $this->io()->title('Example Plugin - Laravel Integration Demo');

        // Check if we're in a Laravel project
        if ($this->isLaravelProject()) {
            $this->io()->success('Laravel project detected!');

            // Get Laravel version
            $version = $this->getLaravelVersion();
            $this->io()->writeln("Laravel version: $version");

            // Check for common packages
            $packages = [
                'pestphp/pest' => $this->hasPest(),
                'phpunit/phpunit' => $this->hasPhpUnit(),
                'laravel/pint' => $this->hasPackage('laravel/pint'),
                'spatie/laravel-ray' => $this->hasPackage('spatie/laravel-ray'),
            ];

            $this->io()->section('Installed Packages');
            foreach ($packages as $package => $installed) {
                $status = $installed ? '✓' : '✗';
                $this->io()->writeln("  $status $package");
            }

            // Example: Run an artisan command (commented out to avoid errors)
            // $this->io()->section('Artisan Command Example');
            // $this->io()->note('Would run: php artisan route:list');
            // $result = $this->artisan('route:list', ['--compact' => true]);

        } else {
            $this->io()->warning('Not in a Laravel project');
            $this->io()->note('Navigate to a Laravel project to see full integration features');
        }

        // Trigger hook
        $this->triggerHook('example_demo_completed', [
            'is_laravel' => $this->isLaravelProject()
        ]);

        return Result::success($this);
    }
}
