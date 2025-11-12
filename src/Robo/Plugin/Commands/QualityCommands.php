<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Code quality commands for Laravel projects
 */
class QualityCommands extends BaseCommand
{
    /**
     * Run Laravel Pint for code style checking
     *
     * @command rover:lint
     * @aliases lint
     * @option $test Run in test mode (don't fix, just report)
     * @option $dirty Only lint uncommitted changes
     */
    public function lint(array $options = ['test' => false, 'dirty' => false]): Result|ResultData
    {
        $this->requireLaravelProject();

        if (!$this->hasPackage('laravel/pint')) {
            $this->error('Laravel Pint is not installed!');
            $this->info('Install it with: composer require laravel/pint --dev');
            return new ResultData(1, "");
        }

        $this->info('Running Laravel Pint...');

        $command = './vendor/bin/pint';

        if ($options['test']) {
            $command .= ' --test';
            $this->say('Running in test mode (no fixes will be applied)');
        }

        if ($options['dirty']) {
            $command .= ' --dirty';
            $this->say('Checking only uncommitted changes');
        }

        $result = $this->taskExec($command)->run();

        if ($result->wasSuccessful()) {
            if ($options['test']) {
                $this->success('Code style is perfect!');
            } else {
                $this->success('Code style fixed!');
            }
        } else {
            if ($options['test']) {
                $this->error('Code style issues found!');
            } else {
                $this->error('Some issues could not be fixed automatically.');
            }
        }

        return $result;
    }

    /**
     * Fix code style issues automatically
     *
     * @command rover:fix
     * @aliases fix
     */
    public function fix(): Result|ResultData
    {
        return $this->lint(['test' => false]);
    }

    /**
     * Check code style without fixing
     *
     * @command rover:check
     * @aliases check
     */
    public function check(): Result|ResultData
    {
        $this->requireLaravelProject();

        $this->info('Running pre-commit checks...');

        $allPassed = true;

        // Check code style
        $this->say("\n1. Checking code style...");
        $lintResult = $this->lint(['test' => true]);

        if (!$lintResult->wasSuccessful()) {
            $allPassed = false;
            $this->error('✗ Code style check failed');
        } else {
            $this->success('✓ Code style check passed');
        }

        // Run tests if available
        if ($this->hasPest() || $this->hasPhpUnit()) {
            $this->say("\n2. Running tests...");
            $testResult = $this->taskExec(
                $this->hasPest() ? './vendor/bin/pest' : './vendor/bin/phpunit'
            )->run();

            if (!$testResult->wasSuccessful()) {
                $allPassed = false;
                $this->error('✗ Tests failed');
            } else {
                $this->success('✓ Tests passed');
            }
        }

        // Run static analysis if PHPStan is available
        if ($this->hasPackage('phpstan/phpstan') || $this->hasPackage('nunomaduro/larastan')) {
            $this->say("\n3. Running static analysis...");
            $stanResult = $this->taskExec('./vendor/bin/phpstan analyse')->run();

            if (!$stanResult->wasSuccessful()) {
                $allPassed = false;
                $this->error('✗ Static analysis failed');
            } else {
                $this->success('✓ Static analysis passed');
            }
        }

        // Final result
        if ($allPassed) {
            $this->success("\n✓ All checks passed!");
            return new ResultData(0, "");
        } else {
            $this->error("\n✗ Some checks failed. Please fix the issues before committing.");
            return new ResultData(1, "");
        }
    }

    /**
     * Run static analysis with PHPStan/Larastan
     *
     * @command rover:analyze
     * @aliases analyze
     */
    public function analyze(): Result|ResultData
    {
        $this->requireLaravelProject();

        if (!$this->hasPackage('phpstan/phpstan') && !$this->hasPackage('nunomaduro/larastan')) {
            $this->error('PHPStan or Larastan is not installed!');
            $this->info('Install Larastan with: composer require nunomaduro/larastan --dev');
            return new ResultData(1, "");
        }

        $this->info('Running static analysis...');

        $result = $this->taskExec('./vendor/bin/phpstan analyse')->run();

        if ($result->wasSuccessful()) {
            $this->success('No issues found!');
        } else {
            $this->error('Static analysis found issues.');
        }

        return $result;
    }

    /**
     * Generate IDE helper files
     *
     * @command rover:ide-helper
     */
    public function ideHelper(): Result|ResultData
    {
        $this->requireLaravelProject();

        if (!$this->hasPackage('barryvdh/laravel-ide-helper')) {
            $this->error('Laravel IDE Helper is not installed!');
            $this->info('Install it with: composer require barryvdh/laravel-ide-helper --dev');
            return new ResultData(1, "");
        }

        $this->info('Generating IDE helper files...');

        // Generate helper file
        $this->say('Generating _ide_helper.php...');
        $this->artisan('ide-helper:generate');

        // Generate model helpers
        $this->say('Generating model helpers...');
        $this->artisan('ide-helper:models', ['nowrite' => true]);

        // Generate PhpStorm meta
        $this->say('Generating PhpStorm meta...');
        $this->artisan('ide-helper:meta');

        $this->success('IDE helper files generated!');
        return new ResultData(0, "");
    }
}
