<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Environment file management commands
 */
class EnvCommands extends BaseCommand
{
    /**
     * Validate environment file for required variables
     *
     * @command rover:env:validate
     * @aliases env:validate
     */
    public function validate(): Result
    {
        $this->requireLaravelProject();

        if (!file_exists('.env')) {
            $this->error('.env file not found!');
            $this->info('Run: cp .env.example .env');
            return Result::error($this);
        }

        $this->info('Validating environment configuration...');

        $env = $this->parseEnvFile('.env');
        $issues = [];

        // Check required variables
        $required = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_DEBUG',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
        ];

        foreach ($required as $key) {
            if (!isset($env[$key]) || empty($env[$key])) {
                $issues[] = "Missing or empty: $key";
            }
        }

        // Check APP_KEY is generated
        if (isset($env['APP_KEY']) && $env['APP_KEY'] === '') {
            $issues[] = 'APP_KEY is not generated. Run: php artisan key:generate';
        }

        // Check production settings
        if (isset($env['APP_ENV']) && $env['APP_ENV'] === 'production') {
            if (isset($env['APP_DEBUG']) && $env['APP_DEBUG'] === 'true') {
                $issues[] = 'APP_DEBUG should be false in production!';
            }
        }

        // Test database connection
        $this->say("\nTesting database connection...");
        $dbTest = $this->testDatabaseConnection();

        if (!$dbTest) {
            $issues[] = 'Database connection failed';
        } else {
            $this->success('✓ Database connection successful');
        }

        // Display results
        if (empty($issues)) {
            $this->success("\n✓ Environment configuration is valid!");
            return Result::success($this);
        } else {
            $this->error("\n✗ Environment validation failed:");
            foreach ($issues as $issue) {
                $this->say("  • $issue");
            }
            return Result::error($this);
        }
    }

    /**
     * Generate .env file from template
     *
     * @command rover:env:generate
     * @option $force Overwrite existing .env file
     */
    public function generate(array $options = ['force' => false]): Result
    {
        $this->requireLaravelProject();

        if (file_exists('.env') && !$options['force']) {
            $this->error('.env file already exists!');
            $this->info('Use --force to overwrite');
            return Result::error($this);
        }

        if (!file_exists('.env.example')) {
            $this->error('.env.example file not found!');
            return Result::error($this);
        }

        $this->info('Generating .env file...');

        // Get project name from current directory
        $projectName = basename(getcwd());
        $dbName = str_replace(['-', ' '], '_', strtolower($projectName));

        // Copy .env.example to .env
        copy('.env.example', '.env');

        // Read .env
        $envContent = file_get_contents('.env');

        // Ask for configuration values
        $appName = $this->io()->ask('Application name', $projectName);
        $appUrl = $this->io()->ask('Application URL', 'http://localhost');
        $dbDatabase = $this->io()->ask('Database name', $dbName);
        $dbUsername = $this->io()->ask('Database username', 'root');
        $dbPassword = $this->io()->askHidden('Database password (leave empty for none)');

        // Replace values
        $replacements = [
            'APP_NAME=Laravel' => "APP_NAME=\"$appName\"",
            'APP_URL=http://localhost' => "APP_URL=$appUrl",
            'DB_DATABASE=laravel' => "DB_DATABASE=$dbDatabase",
            'DB_USERNAME=root' => "DB_USERNAME=$dbUsername",
        ];

        foreach ($replacements as $search => $replace) {
            $envContent = str_replace($search, $replace, $envContent);
        }

        // Add password if provided
        if ($dbPassword) {
            $envContent = preg_replace(
                '/DB_PASSWORD=.*$/m',
                "DB_PASSWORD=$dbPassword",
                $envContent
            );
        }

        file_put_contents('.env', $envContent);

        // Generate application key
        $this->say("\nGenerating application key...");
        $this->artisan('key:generate');

        $this->success('✓ .env file generated successfully!');
        $this->info("\nRun: vendor/bin/robo rover:env:validate to test your configuration");

        return Result::success($this);
    }

    /**
     * Compare .env with .env.example
     *
     * @command rover:env:compare
     */
    public function compare(): Result
    {
        $this->requireLaravelProject();

        if (!file_exists('.env.example')) {
            $this->error('.env.example not found!');
            return Result::error($this);
        }

        $this->info('Comparing .env with .env.example...');

        $example = $this->parseEnvFile('.env.example');
        $current = file_exists('.env') ? $this->parseEnvFile('.env') : [];

        $missing = [];
        $extra = [];

        // Find missing variables
        foreach (array_keys($example) as $key) {
            if (!isset($current[$key])) {
                $missing[] = $key;
            }
        }

        // Find extra variables
        foreach (array_keys($current) as $key) {
            if (!isset($example[$key])) {
                $extra[] = $key;
            }
        }

        if (empty($missing) && empty($extra)) {
            $this->success('✓ .env matches .env.example');
            return Result::success($this);
        }

        if (!empty($missing)) {
            $this->warning("\nMissing variables in .env:");
            foreach ($missing as $key) {
                $this->say("  • $key");
            }
        }

        if (!empty($extra)) {
            $this->info("\nExtra variables in .env (not in .env.example):");
            foreach ($extra as $key) {
                $this->say("  • $key");
            }
        }

        return Result::success($this);
    }

    /**
     * Show environment information
     *
     * @command rover:env:info
     */
    public function info(): Result
    {
        $this->requireLaravelProject();

        if (!file_exists('.env')) {
            $this->error('.env file not found!');
            return Result::error($this);
        }

        $env = $this->parseEnvFile('.env');

        $this->info('Environment Information:');
        $this->say('');

        // Display key information (without sensitive data)
        $safeKeys = [
            'APP_NAME',
            'APP_ENV',
            'APP_DEBUG',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'CACHE_DRIVER',
            'QUEUE_CONNECTION',
            'SESSION_DRIVER',
        ];

        foreach ($safeKeys as $key) {
            if (isset($env[$key])) {
                $value = $env[$key];
                $this->say("  $key: $value");
            }
        }

        $this->say('');
        $this->info('(Sensitive values like passwords are hidden)');

        return Result::success($this);
    }

    /**
     * Parse .env file into array
     *
     * @param string $file
     * @return array
     */
    protected function parseEnvFile(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $env = [];
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes
                $value = trim($value, '"\'');

                $env[$key] = $value;
            }
        }

        return $env;
    }

    /**
     * Test database connection
     *
     * @return bool
     */
    protected function testDatabaseConnection(): bool
    {
        $result = $this->taskExec('php artisan db:show')
            ->printOutput(false)
            ->run();

        return $result->wasSuccessful();
    }

    /**
     * Check for exposed secrets in .env file
     *
     * @command rover:env:check-secrets
     */
    public function checkSecrets(): Result
    {
        $this->requireLaravelProject();

        $this->info('Checking for potentially exposed secrets...');

        $warnings = [];

        // Check if .env is in git
        $gitCheck = $this->taskExec('git check-ignore .env')
            ->printOutput(false)
            ->run();

        if (!$gitCheck->wasSuccessful()) {
            $warnings[] = '.env file may not be properly ignored by git!';
        }

        // Check if .env is tracked
        $gitLsFiles = $this->taskExec('git ls-files .env')
            ->printOutput(false)
            ->run();

        if ($gitLsFiles->wasSuccessful() && trim($gitLsFiles->getMessage())) {
            $warnings[] = '.env file is tracked by git! This is dangerous!';
        }

        // Check for common secret patterns in tracked files
        $secretPatterns = [
            'password',
            'secret',
            'api_key',
            'token',
            'aws_',
        ];

        if (empty($warnings)) {
            $this->success('✓ No exposed secrets detected');
            return Result::success($this);
        } else {
            $this->warning('⚠ Potential security issues found:');
            foreach ($warnings as $warning) {
                $this->say("  • $warning");
            }

            if (in_array('.env file is tracked by git! This is dangerous!', $warnings)) {
                $this->error("\nIMPORTANT: Remove .env from git immediately!");
                $this->info("Run: git rm --cached .env");
            }

            return Result::error($this);
        }
    }
}
