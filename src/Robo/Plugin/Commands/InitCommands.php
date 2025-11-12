<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Rover\Config\Config;

/**
 * Initialization and setup commands for Rover
 */
class InitCommands extends BaseCommand
{
    /**
     * Initialize Rover configuration for a project
     *
     * Creates a rover.yml configuration file with team defaults.
     *
     * @command rover:init
     * @aliases init
     */
    public function init(): Result
    {
        $this->info('Initializing Rover configuration...');

        if (file_exists('rover.yml')) {
            $overwrite = $this->io()->confirm('rover.yml already exists. Overwrite?', false);

            if (!$overwrite) {
                $this->info('Initialization cancelled.');
                return Result::cancelled();
            }
        }

        // Create default configuration
        if (Config::createDefault('rover.yml')) {
            $this->success('Created rover.yml configuration file!');
            $this->info('Edit rover.yml to customize settings for your team.');
        } else {
            $this->error('Failed to create rover.yml');
            return Result::error($this);
        }

        // Check if this is a Laravel project
        if ($this->isLaravelProject()) {
            $this->say("\n✓ Laravel project detected!");

            // Offer to install recommended packages
            if ($this->io()->confirm('Install recommended development packages?', true)) {
                $this->installRecommendedPackages();
            }

            // Offer to generate IDE helpers
            if ($this->hasPackage('barryvdh/laravel-ide-helper')) {
                if ($this->io()->confirm('Generate IDE helper files?', true)) {
                    $this->artisan('ide-helper:generate');
                    $this->artisan('ide-helper:meta');
                    $this->success('IDE helper files generated!');
                }
            }
        }

        $this->success("\nRover is ready to use!");
        $this->info("Try running: rover:test, rover:lint, rover:fresh");

        return Result::success($this);
    }

    /**
     * Install recommended development packages
     */
    protected function installRecommendedPackages(): void
    {
        $packages = [
            'laravel/pint' => 'Code style fixer',
            'barryvdh/laravel-ide-helper' => 'IDE autocompletion',
            'pestphp/pest' => 'Testing framework',
            'pestphp/pest-plugin-laravel' => 'Pest Laravel plugin',
        ];

        $toInstall = [];

        foreach ($packages as $package => $description) {
            if (!$this->hasPackage($package)) {
                $toInstall[] = $package;
            }
        }

        if (empty($toInstall)) {
            $this->info('All recommended packages are already installed!');
            return;
        }

        $this->say('Installing packages: ' . implode(', ', $toInstall));

        $result = $this->taskComposerRequire()
            ->dev()
            ->args(implode(' ', $toInstall))
            ->run();

        if ($result->wasSuccessful()) {
            $this->success('Packages installed successfully!');

            // Set up Pest if it was installed
            if (in_array('pestphp/pest', $toInstall)) {
                $this->say('Setting up Pest...');
                $this->artisan('pest:install');
            }
        } else {
            $this->warning('Some packages failed to install. You can install them manually.');
        }
    }

    /**
     * Show Rover status and configuration
     *
     * @command rover:status
     * @aliases status
     */
    public function status(): Result
    {
        $this->info('Rover Status:');
        $this->say('');

        // Check if in Laravel project
        if ($this->isLaravelProject()) {
            $this->say('✓ Laravel project detected');
            $version = $this->getLaravelVersion();
            if ($version) {
                $this->say("  Laravel version: $version");
            }
        } else {
            $this->say('✗ Not a Laravel project');
        }

        $this->say('');

        // Check for configuration file
        if (file_exists('rover.yml')) {
            $this->say('✓ rover.yml configuration found');
        } else {
            $this->say('○ No rover.yml configuration (run rover:init to create one)');
        }

        $this->say('');

        // Check installed tools
        $this->say('Installed Tools:');

        $tools = [
            'laravel/pint' => 'Code style fixer',
            'pestphp/pest' => 'Pest testing framework',
            'phpunit/phpunit' => 'PHPUnit testing framework',
            'barryvdh/laravel-ide-helper' => 'IDE helper',
            'nunomaduro/larastan' => 'Static analysis',
            'spatie/laravel-ray' => 'Debugging tool',
        ];

        foreach ($tools as $package => $description) {
            if ($this->hasPackage($package)) {
                $this->say("  ✓ $description ($package)");
            } else {
                $this->say("  ○ $description ($package) - not installed");
            }
        }

        return Result::success($this);
    }

    /**
     * Display Rover version and information
     *
     * @command rover:about
     * @aliases about
     */
    public function about(): Result
    {
        $this->say('');
        $this->say('  ╭─────────────────────────────────────────╮');
        $this->say('  │                                         │');
        $this->say('  │           Rover - by StuntRocket        │');
        $this->say('  │                                         │');
        $this->say('  ╰─────────────────────────────────────────╯');
        $this->say('');
        $this->say('  Opinionated Laravel development assistant');
        $this->say('  for teams who value quality and standards.');
        $this->say('');
        $this->say('  Available Commands:');
        $this->say('    rover:init       - Initialize configuration');
        $this->say('    rover:fresh      - Fresh database migration');
        $this->say('    rover:clear      - Clear all caches');
        $this->say('    rover:optimize   - Optimize application');
        $this->say('    rover:test       - Run tests');
        $this->say('    rover:lint       - Check code style');
        $this->say('    rover:fix        - Fix code style');
        $this->say('    rover:check      - Pre-commit checks');
        $this->say('');
        $this->say('  Run "vendor/bin/robo list" for all commands');
        $this->say('');

        return Result::success($this);
    }
}
