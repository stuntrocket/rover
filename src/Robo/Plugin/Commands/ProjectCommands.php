<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Project scaffolding and setup commands
 */
class ProjectCommands extends BaseCommand
{
    /**
     * Create a new Laravel project with opinionated defaults
     *
     * @command rover:new
     * @param string $name Project name
     * @option $stack The starter kit to use (breeze, jetstream, none)
     * @option $pest Use Pest for testing instead of PHPUnit
     * @option $git Initialize git repository
     * @option $force Force creation even if directory exists
     */
    public function newProject(
        string $name,
        array $options = [
            'stack' => 'none',
            'pest' => true,
            'git' => true,
            'force' => false
        ]
    ): Result {
        $this->info("Creating new Laravel project: $name");

        // Check if directory exists
        if (is_dir($name) && !$options['force']) {
            $this->error("Directory '$name' already exists!");
            $this->info('Use --force to create anyway (this will overwrite files).');
            return new ResultData(1, "");
        }

        // Create Laravel project
        $this->say('Installing Laravel...');
        $createCommand = "composer create-project laravel/laravel $name";

        $result = $this->taskExec($createCommand)->run();

        if (!$result->wasSuccessful()) {
            $this->error('Failed to create Laravel project!');
            return new ResultData(1, "");
        }

        $this->success('Laravel installed!');

        // Change to project directory for subsequent commands
        $originalDir = getcwd();
        chdir($name);

        try {
            // Install opinionated packages
            $this->say("\nInstalling development packages...");
            $this->installDevelopmentPackages();

            // Set up Pest if requested
            if ($options['pest']) {
                $this->say("\nSetting up Pest...");
                $this->setupPest();
            }

            // Install starter kit if requested
            if ($options['stack'] !== 'none') {
                $this->say("\nInstalling {$options['stack']}...");
                $this->installStarterKit($options['stack']);
            }

            // Create opinionated directory structure
            $this->say("\nCreating directory structure...");
            $this->createDirectoryStructure();

            // Set up configuration files
            $this->say("\nSetting up configuration files...");
            $this->setupConfigurationFiles();

            // Initialize git if requested
            if ($options['git']) {
                $this->say("\nInitializing git...");
                $this->setupGit();
            }

            // Generate IDE helpers
            $this->say("\nGenerating IDE helpers...");
            $this->artisan('ide-helper:generate');
            $this->artisan('ide-helper:meta');

            // Create rover.yml
            $this->say("\nCreating Rover configuration...");
            \Rover\Config\Config::createDefault('rover.yml');

            $this->success("\n✨ Project '$name' created successfully!");
            $this->info("\nNext steps:");
            $this->say("  cd $name");
            $this->say("  cp .env.example .env");
            $this->say("  php artisan key:generate");
            $this->say("  vendor/bin/robo rover:fresh");
            $this->say("  php artisan serve");

        } finally {
            // Return to original directory
            chdir($originalDir);
        }

        return new ResultData(0, "");
    }

    /**
     * Install development packages
     */
    protected function installDevelopmentPackages(): void
    {
        $packages = [
            'laravel/pint',
            'barryvdh/laravel-ide-helper',
            'pestphp/pest',
            'pestphp/pest-plugin-laravel',
            'spatie/laravel-ray',
            'nunomaduro/larastan',
        ];

        $this->taskComposerRequire()
            ->dev()
            ->args(implode(' ', $packages))
            ->option('--no-interaction')
            ->run();
    }

    /**
     * Set up Pest testing framework
     */
    protected function setupPest(): void
    {
        $this->artisan('pest:install');

        // Create Pest.php configuration if it doesn't exist
        if (!file_exists('tests/Pest.php')) {
            $pestConfig = <<<'PHP'
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
    RefreshDatabase::class,
)->in('Feature');

uses(Tests\TestCase::class)->in('Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something(): bool
{
    return true;
}

PHP;
            file_put_contents('tests/Pest.php', $pestConfig);
        }
    }

    /**
     * Install starter kit (Breeze, Jetstream, etc.)
     */
    protected function installStarterKit(string $stack): void
    {
        switch ($stack) {
            case 'breeze':
                $this->taskComposerRequire()
                    ->dev()
                    ->dependency('laravel/breeze')
                    ->run();

                $this->artisan('breeze:install', ['stack' => 'blade']);
                break;

            case 'jetstream':
                $this->taskComposerRequire()
                    ->dependency('laravel/jetstream')
                    ->run();

                $this->artisan('jetstream:install', ['stack' => 'livewire']);
                break;
        }
    }

    /**
     * Create opinionated directory structure
     */
    protected function createDirectoryStructure(): void
    {
        $directories = [
            'app/Actions',
            'app/Services',
            'app/DataTransferObjects',
            'app/Enums',
            'app/Traits',
            'app/Repositories',
            'app/QueryBuilders',
            'tests/Feature/Api',
            'tests/Feature/Auth',
            'tests/Feature/Admin',
            'tests/Unit/Actions',
            'tests/Unit/Services',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                // Create .gitkeep
                touch("$dir/.gitkeep");
            }
        }
    }

    /**
     * Set up configuration files
     */
    protected function setupConfigurationFiles(): void
    {
        // Create pint.json
        $pintConfig = [
            'preset' => 'laravel',
            'rules' => [
                'array_syntax' => ['syntax' => 'short'],
                'ordered_imports' => ['sort_algorithm' => 'alpha'],
                'no_unused_imports' => true,
                'not_operator_with_successor_space' => true,
                'trailing_comma_in_multiline' => true,
                'phpdoc_scalar' => true,
                'unary_operator_spaces' => true,
                'binary_operator_spaces' => true,
                'blank_line_before_statement' => [
                    'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
                ],
                'phpdoc_single_line_var_spacing' => true,
                'phpdoc_var_without_name' => true,
            ],
        ];

        file_put_contents('pint.json', json_encode($pintConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Create .editorconfig
        $editorConfig = <<<'INI'
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[*.md]
trim_trailing_whitespace = false

[*.{yml,yaml}]
indent_size = 2

[*.{js,jsx,ts,tsx,vue}]
indent_size = 2

[docker-compose.yml]
indent_size = 2

INI;

        file_put_contents('.editorconfig', $editorConfig);

        // Create phpstan.neon
        $phpstanConfig = <<<'NEON'
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:
    paths:
        - app/

    level: 5

    ignoreErrors:

    excludePaths:

    checkMissingIterableValueType: false

NEON;

        file_put_contents('phpstan.neon', $phpstanConfig);
    }

    /**
     * Set up git repository
     */
    protected function setupGit(): void
    {
        // Initialize git if not already initialized
        if (!is_dir('.git')) {
            $this->taskExec('git init')->run();
        }

        // Create/update .gitignore
        $gitignore = <<<'GITIGNORE'
/.phpunit.cache
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
_ide_helper.php
_ide_helper_models.php
.phpstorm.meta.php

GITIGNORE;

        file_put_contents('.gitignore', $gitignore);

        // Initial commit
        $this->taskExec('git add .')->run();
        $this->taskExec('git commit -m "Initial commit: Laravel with Rover setup"')->run();
    }

    /**
     * Set up an existing Laravel project with Rover standards
     *
     * @command rover:setup
     * @aliases setup
     */
    public function setupExisting(): Result
    {
        $this->requireLaravelProject();

        $this->info('Setting up Laravel project with Rover standards...');

        if (!$this->io()->confirm('This will install packages and create configuration files. Continue?', true)) {
            return Result::cancelled();
        }

        // Install packages
        $this->say("\nInstalling development packages...");
        $this->installDevelopmentPackages();

        // Create directory structure
        $this->say("\nCreating directory structure...");
        $this->createDirectoryStructure();

        // Set up configuration files
        $this->say("\nSetting up configuration files...");
        $this->setupConfigurationFiles();

        // Set up Pest if desired
        if ($this->io()->confirm('Set up Pest testing framework?', true)) {
            $this->say("\nSetting up Pest...");
            $this->setupPest();
        }

        // Generate IDE helpers
        if ($this->hasPackage('barryvdh/laravel-ide-helper')) {
            $this->say("\nGenerating IDE helpers...");
            $this->artisan('ide-helper:generate');
            $this->artisan('ide-helper:meta');
        }

        // Create rover.yml if it doesn't exist
        if (!file_exists('rover.yml')) {
            $this->say("\nCreating Rover configuration...");
            \Rover\Config\Config::createDefault('rover.yml');
        }

        $this->success("\n✨ Project set up with Rover standards!");
        $this->info("\nNext steps:");
        $this->say("  vendor/bin/robo rover:check    # Run quality checks");
        $this->say("  vendor/bin/robo rover:test     # Run tests");
        $this->say("  vendor/bin/robo rover:git:hooks # Install git hooks");

        return new ResultData(0, "");
    }
}
