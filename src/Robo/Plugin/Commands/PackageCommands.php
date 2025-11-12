<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Laravel package development commands
 */
class PackageCommands extends BaseCommand
{
    /**
     * Initialize a new Laravel package
     *
     * @command rover:package:init
     * @param string $name Package name (vendor/package)
     * @option $path Path where package will be created
     */
    public function init(string $name, array $options = ['path' => 'packages']): Result
    {
        // Parse vendor and package name
        if (!str_contains($name, '/')) {
            $this->error('Package name must be in format: vendor/package');
            return new ResultData(1, "");
        }

        [$vendor, $package] = explode('/', $name);

        $this->info("Creating Laravel package: $name");

        // Create package directory
        $packagePath = $options['path'] . '/' . $vendor . '/' . $package;

        if (is_dir($packagePath)) {
            $this->error("Package directory already exists: $packagePath");
            return new ResultData(1, "");
        }

        mkdir($packagePath, 0755, true);

        // Create package structure
        $this->say('Creating package structure...');
        $this->createPackageStructure($packagePath, $vendor, $package);

        // Create package files
        $this->say('Creating package files...');
        $this->createComposerJson($packagePath, $vendor, $package);
        $this->createServiceProvider($packagePath, $vendor, $package);
        $this->createReadme($packagePath, $vendor, $package);
        $this->createGitignore($packagePath);
        $this->createPhpUnitXml($packagePath);
        $this->createGitHubWorkflow($packagePath, $vendor, $package);

        $this->success("✓ Package created at: $packagePath");
        $this->say('');
        $this->info('Next steps:');
        $this->say("  cd $packagePath");
        $this->say("  composer install");
        $this->say("  vendor/bin/robo rover:package:link");

        return new ResultData(0, "");
    }

    /**
     * Link package for local development
     *
     * @command rover:package:link
     * @param string|null $packagePath Path to package
     */
    public function link(?string $packagePath = null): Result
    {
        $this->requireLaravelProject();

        if (!$packagePath) {
            $packagePath = '.';
        }

        // Check if composer.json exists
        $composerFile = "$packagePath/composer.json";
        if (!file_exists($composerFile)) {
            $this->error('No composer.json found in package path');
            return new ResultData(1, "");
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $packageName = $composer['name'] ?? null;

        if (!$packageName) {
            $this->error('Package name not found in composer.json');
            return new ResultData(1, "");
        }

        $this->info("Linking package: $packageName");

        // Get absolute path
        $absolutePath = realpath($packagePath);

        // Update composer.json with path repository
        $projectComposer = json_decode(file_get_contents('composer.json'), true);

        // Add path repository
        if (!isset($projectComposer['repositories'])) {
            $projectComposer['repositories'] = [];
        }

        $projectComposer['repositories'][] = [
            'type' => 'path',
            'url' => $absolutePath,
            'options' => [
                'symlink' => true,
            ],
        ];

        // Write updated composer.json
        file_put_contents(
            'composer.json',
            json_encode($projectComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->say('Updated composer.json with path repository');

        // Run composer require
        $this->say("Running: composer require $packageName @dev");
        $result = $this->taskExec("composer require $packageName @dev")->run();

        if ($result->wasSuccessful()) {
            $this->success("✓ Package linked successfully!");
            $this->info("Package is now symlinked to vendor/$packageName");
        }

        return $result;
    }

    /**
     * Unlink package
     *
     * @command rover:package:unlink
     * @param string $packageName Package name (vendor/package)
     */
    public function unlink(string $packageName): Result
    {
        $this->requireLaravelProject();

        $this->info("Unlinking package: $packageName");

        // Remove from composer
        $this->say('Removing package...');
        $result = $this->taskExec("composer remove $packageName")->run();

        if (!$result->wasSuccessful()) {
            return $result;
        }

        // Remove path repository from composer.json
        $composer = json_decode(file_get_contents('composer.json'), true);

        if (isset($composer['repositories'])) {
            $composer['repositories'] = array_filter(
                $composer['repositories'],
                function ($repo) use ($packageName) {
                    // Keep repositories that don't contain this package
                    if (!isset($repo['url'])) {
                        return true;
                    }

                    return !str_contains($repo['url'], $packageName);
                }
            );

            // Re-index array
            $composer['repositories'] = array_values($composer['repositories']);

            file_put_contents(
                'composer.json',
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
        }

        $this->success('✓ Package unlinked successfully!');

        return new ResultData(0, "");
    }

    /**
     * Run package tests
     *
     * @command rover:package:test
     * @param string|null $packagePath Path to package
     */
    public function testPackage(?string $packagePath = null): Result
    {
        if (!$packagePath) {
            $packagePath = '.';
        }

        $this->info('Running package tests...');

        $originalDir = getcwd();
        chdir($packagePath);

        // Check for vendor/bin/phpunit or vendor/bin/pest
        $testCommand = file_exists('vendor/bin/pest') ? 'vendor/bin/pest' : 'vendor/bin/phpunit';

        if (!file_exists($testCommand)) {
            $this->error('Test runner not found. Run composer install first.');
            chdir($originalDir);
            return new ResultData(1, "");
        }

        $result = $this->taskExec($testCommand)->run();

        chdir($originalDir);

        if ($result->wasSuccessful()) {
            $this->success('✓ All tests passed!');
        }

        return $result;
    }

    /**
     * Prepare package for publishing
     *
     * @command rover:package:publish
     * @param string|null $packagePath Path to package
     */
    public function preparePublish(?string $packagePath = null): Result
    {
        if (!$packagePath) {
            $packagePath = '.';
        }

        $this->info('Preparing package for publishing...');

        $checks = [];

        // Check composer.json
        $composerFile = "$packagePath/composer.json";
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);

            if (isset($composer['name'])) {
                $this->say('✓ Package name: ' . $composer['name']);
                $checks[] = true;
            } else {
                $this->error('✗ Package name missing');
                $checks[] = false;
            }

            if (isset($composer['description'])) {
                $this->say('✓ Description provided');
                $checks[] = true;
            } else {
                $this->warning('⚠ Description missing');
                $checks[] = false;
            }

            if (isset($composer['license'])) {
                $this->say('✓ License: ' . $composer['license']);
                $checks[] = true;
            } else {
                $this->warning('⚠ License missing');
                $checks[] = false;
            }

            if (isset($composer['authors']) && !empty($composer['authors'])) {
                $this->say('✓ Authors provided');
                $checks[] = true;
            } else {
                $this->warning('⚠ Authors missing');
                $checks[] = false;
            }
        }

        // Check README
        if (file_exists("$packagePath/README.md")) {
            $this->say('✓ README.md exists');
            $checks[] = true;
        } else {
            $this->error('✗ README.md missing');
            $checks[] = false;
        }

        // Check LICENSE
        if (file_exists("$packagePath/LICENSE") || file_exists("$packagePath/LICENSE.md")) {
            $this->say('✓ LICENSE file exists');
            $checks[] = true;
        } else {
            $this->warning('⚠ LICENSE file missing');
            $checks[] = false;
        }

        // Check tests
        if (is_dir("$packagePath/tests")) {
            $testFiles = glob("$packagePath/tests/**/*Test.php");
            if (!empty($testFiles)) {
                $this->say('✓ Tests found: ' . count($testFiles));
                $checks[] = true;
            } else {
                $this->warning('⚠ No test files found');
                $checks[] = false;
            }
        } else {
            $this->warning('⚠ Tests directory missing');
            $checks[] = false;
        }

        // Summary
        $this->say('');
        $passed = count(array_filter($checks));
        $total = count($checks);

        if ($passed === $total) {
            $this->success("✓ Package is ready to publish! ($passed/$total checks passed)");
        } else {
            $this->warning("⚠ Package has issues ($passed/$total checks passed)");
            $this->info("\nRecommended actions:");
            $this->say("  - Add missing information to composer.json");
            $this->say("  - Create LICENSE file");
            $this->say("  - Write tests for your package");
            $this->say("  - Complete README documentation");
        }

        return new ResultData(0, "");
    }

    /**
     * Generate package documentation
     *
     * @command rover:package:docs
     * @param string|null $packagePath Path to package
     */
    public function generateDocs(?string $packagePath = null): Result
    {
        if (!$packagePath) {
            $packagePath = '.';
        }

        $this->info('Generating package documentation...');

        $composerFile = "$packagePath/composer.json";
        if (!file_exists($composerFile)) {
            $this->error('composer.json not found');
            return new ResultData(1, "");
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $packageName = $composer['name'] ?? 'Package';

        // Generate README template
        $readme = $this->generateReadmeTemplate($composer);

        $readmePath = "$packagePath/README.md";

        if (file_exists($readmePath)) {
            if (!$this->io()->confirm('README.md already exists. Overwrite?', false)) {
                return Result::cancelled();
            }
        }

        file_put_contents($readmePath, $readme);

        $this->success("✓ Documentation generated: $readmePath");

        return new ResultData(0, "");
    }

    /**
     * Create package structure
     */
    protected function createPackageStructure(string $path, string $vendor, string $package): void
    {
        $dirs = [
            'src',
            'tests',
            'tests/Feature',
            'tests/Unit',
            'config',
            'database/migrations',
            'resources/views',
            '.github/workflows',
        ];

        foreach ($dirs as $dir) {
            mkdir("$path/$dir", 0755, true);
        }
    }

    /**
     * Create composer.json
     */
    protected function createComposerJson(string $path, string $vendor, string $package): void
    {
        $namespace = ucfirst($vendor) . '\\' . ucfirst($package);

        $composer = [
            'name' => "$vendor/$package",
            'description' => 'A Laravel package',
            'type' => 'library',
            'license' => 'MIT',
            'authors' => [
                [
                    'name' => 'Your Name',
                    'email' => 'your@email.com',
                ],
            ],
            'require' => [
                'php' => '^8.1',
                'illuminate/support' => '^10.0',
            ],
            'require-dev' => [
                'orchestra/testbench' => '^8.0',
                'pestphp/pest' => '^2.0',
                'pestphp/pest-plugin-laravel' => '^2.0',
            ],
            'autoload' => [
                'psr-4' => [
                    "$namespace\\" => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "$namespace\\Tests\\" => 'tests/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "$namespace\\{$package}ServiceProvider",
                    ],
                ],
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];

        file_put_contents(
            "$path/composer.json",
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    /**
     * Create service provider
     */
    protected function createServiceProvider(string $path, string $vendor, string $package): void
    {
        $namespace = ucfirst($vendor) . '\\' . ucfirst($package);
        $className = ucfirst($package) . 'ServiceProvider';

        $content = <<<PHP
<?php

namespace $namespace;

use Illuminate\Support\ServiceProvider;

class $className extends ServiceProvider
{
    public function register(): void
    {
        // Register package services
    }

    public function boot(): void
    {
        // Publish config
        \$this->publishes([
            __DIR__.'/../config/$package.php' => config_path('$package.php'),
        ], '$package-config');

        // Load migrations
        \$this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        \$this->loadViewsFrom(__DIR__.'/../resources/views', '$package');
    }
}

PHP;

        file_put_contents("$path/src/$className.php", $content);
    }

    /**
     * Create README template
     */
    protected function createReadme(string $path, string $vendor, string $package): void
    {
        $packageName = "$vendor/$package";

        $readme = <<<MD
# $packageName

A Laravel package for...

## Installation

Install the package via composer:

```bash
composer require $packageName
```

## Usage

```php
// Usage example
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Your Name](https://github.com/yourusername)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
MD;

        file_put_contents("$path/README.md", $readme);
    }

    /**
     * Create .gitignore
     */
    protected function createGitignore(string $path): void
    {
        $gitignore = <<<'GITIGNORE'
/vendor/
.phpunit.result.cache
.php-cs-fixer.cache
composer.lock
.idea
.vscode
.DS_Store
GITIGNORE;

        file_put_contents("$path/.gitignore", $gitignore);
    }

    /**
     * Create phpunit.xml
     */
    protected function createPhpUnitXml(string $path): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Package Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
XML;

        file_put_contents("$path/phpunit.xml", $xml);
    }

    /**
     * Create GitHub Actions workflow
     */
    protected function createGitHubWorkflow(string $path, string $vendor, string $package): void
    {
        $workflow = <<<'YAML'
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.1', '8.2']
        laravel: ['^10.0']

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, dom, fileinfo

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Tests
        run: vendor/bin/pest
YAML;

        file_put_contents("$path/.github/workflows/tests.yml", $workflow);
    }

    /**
     * Generate README template
     */
    protected function generateReadmeTemplate(array $composer): string
    {
        $name = $composer['name'] ?? 'package';
        $description = $composer['description'] ?? 'A Laravel package';

        return <<<MD
# $name

$description

## Installation

```bash
composer require $name
```

## Usage

```php
// Usage examples
```

## Testing

```bash
composer test
```

## License

MIT
MD;
    }
}
