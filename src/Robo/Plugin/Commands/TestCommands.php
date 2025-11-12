<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Testing commands for Laravel projects with smart Pest/PHPUnit detection
 */
class TestCommands extends BaseCommand
{
    /**
     * Run tests with smart detection of Pest or PHPUnit
     *
     * Automatically detects and runs the appropriate test runner.
     *
     * @command rover:test
     * @aliases test
     * @option $filter Filter tests by pattern
     * @option $group Run tests from specific group
     * @option $coverage Generate code coverage report
     * @option $parallel Run tests in parallel
     */
    public function test(array $options = [
        'filter' => null,
        'group' => null,
        'coverage' => false,
        'parallel' => false
    ]): Result
    {
        $this->requireLaravelProject();

        // Detect test runner
        if ($this->hasPest()) {
            return $this->runPest($options);
        } elseif ($this->hasPhpUnit()) {
            return $this->runPhpUnit($options);
        } else {
            $this->error('No test runner found! Please install Pest or PHPUnit.');
            return new ResultData(1, "");
        }
    }

    /**
     * Run Pest tests
     *
     * @param array $options
     * @return Result
     */
    protected function runPest(array $options): Result
    {
        $this->info('Running tests with Pest...');

        $command = './vendor/bin/pest';

        // Add filter
        if ($options['filter']) {
            $command .= " --filter={$options['filter']}";
        }

        // Add group
        if ($options['group']) {
            $command .= " --group={$options['group']}";
        }

        // Add coverage
        if ($options['coverage']) {
            $command .= ' --coverage';
        }

        // Add parallel
        if ($options['parallel']) {
            $command .= ' --parallel';
        }

        $result = $this->taskExec($command)->run();

        if ($result->wasSuccessful()) {
            $this->success('All tests passed!');
        } else {
            $this->error('Some tests failed.');
        }

        return $result;
    }

    /**
     * Run PHPUnit tests
     *
     * @param array $options
     * @return Result
     */
    protected function runPhpUnit(array $options): Result
    {
        $this->info('Running tests with PHPUnit...');

        $command = './vendor/bin/phpunit';

        // Add filter
        if ($options['filter']) {
            $command .= " --filter={$options['filter']}";
        }

        // Add group
        if ($options['group']) {
            $command .= " --group={$options['group']}";
        }

        // Add coverage
        if ($options['coverage']) {
            $command .= ' --coverage-html coverage';
            $this->info('Coverage report will be generated in ./coverage directory');
        }

        $result = $this->taskExec($command)->run();

        if ($result->wasSuccessful()) {
            $this->success('All tests passed!');
        } else {
            $this->error('Some tests failed.');
        }

        return $result;
    }

    /**
     * Run tests with coverage report
     *
     * @command rover:coverage
     * @aliases coverage
     */
    public function coverage(): Result
    {
        return $this->test(['coverage' => true]);
    }

    /**
     * Run a specific test file
     *
     * @command rover:test:file
     * @param string $file Path to test file
     */
    public function testFile(string $file): Result
    {
        $this->requireLaravelProject();

        if (!file_exists($file)) {
            $this->error("Test file not found: $file");
            return new ResultData(1, "");
        }

        $this->info("Running test file: $file");

        if ($this->hasPest()) {
            $result = $this->taskExec("./vendor/bin/pest $file")->run();
        } elseif ($this->hasPhpUnit()) {
            $result = $this->taskExec("./vendor/bin/phpunit $file")->run();
        } else {
            $this->error('No test runner found!');
            return new ResultData(1, "");
        }

        if ($result->wasSuccessful()) {
            $this->success('Test passed!');
        } else {
            $this->error('Test failed.');
        }

        return $result;
    }

    /**
     * List available test suites
     *
     * @command rover:test:list
     */
    public function listTests(): Result
    {
        $this->requireLaravelProject();

        $this->info('Available test directories:');

        $testDirs = ['tests/Feature', 'tests/Unit', 'tests/Integration'];
        $found = false;

        foreach ($testDirs as $dir) {
            if (is_dir($dir)) {
                $found = true;
                $files = $this->getTestFiles($dir);
                $this->say("\n$dir (" . count($files) . " tests)");

                foreach ($files as $file) {
                    $this->say("  - " . basename($file));
                }
            }
        }

        if (!$found) {
            $this->warning('No test directories found.');
        }

        return new ResultData(0, "");
    }

    /**
     * Get test files from directory
     *
     * @param string $directory
     * @return array
     */
    protected function getTestFiles(string $directory): array
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
