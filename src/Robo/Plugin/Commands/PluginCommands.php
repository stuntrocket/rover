<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Rover\Plugin\PluginManager;

/**
 * Plugin management commands
 */
class PluginCommands extends BaseCommand
{
    /**
     * List all available plugins
     *
     * @command plugin:list
     * @aliases plugins
     *
     * @option bool $loaded Show only loaded plugins
     */
    public function list(array $options = ['loaded' => false]): Result
    {
        $pluginManager = PluginManager::getInstance();

        if ($options['loaded']) {
            $plugins = $pluginManager->getLoadedPlugins();
            $this->io()->title('Loaded Plugins');
        } else {
            $plugins = $pluginManager->getPlugins();
            $this->io()->title('Available Plugins');
        }

        if (empty($plugins)) {
            $this->io()->note('No plugins found');
            return Result::success($this);
        }

        $rows = [];
        foreach ($plugins as $name => $plugin) {
            $metadata = $plugin['metadata'];
            $status = $plugin['enabled'] ? '✓' : '✗';
            $loaded = $pluginManager->isPluginLoaded($name) ? 'Yes' : 'No';

            $rows[] = [
                $status,
                $name,
                $metadata['version'] ?? 'N/A',
                $metadata['description'] ?? '',
                $loaded,
            ];
        }

        $this->io()->table(
            ['Status', 'Name', 'Version', 'Description', 'Loaded'],
            $rows
        );

        $this->io()->note([
            'Total plugins: ' . count($plugins),
            'Loaded: ' . count($pluginManager->getLoadedPlugins()),
        ]);

        return Result::success($this);
    }

    /**
     * Show detailed information about a plugin
     *
     * @command plugin:info
     *
     * @param string $name Plugin name
     */
    public function info(string $name): Result
    {
        $pluginManager = PluginManager::getInstance();
        $plugin = $pluginManager->getPlugin($name);

        if (!$plugin) {
            $this->io()->error("Plugin not found: $name");
            return Result::error($this);
        }

        $metadata = $plugin['metadata'];
        $isLoaded = $pluginManager->isPluginLoaded($name);

        $this->io()->title("Plugin: $name");

        $info = [
            'Name' => $metadata['name'] ?? 'N/A',
            'Version' => $metadata['version'] ?? 'N/A',
            'Description' => $metadata['description'] ?? 'N/A',
            'Author' => $metadata['author'] ?? 'N/A',
            'License' => $metadata['license'] ?? 'N/A',
            'Path' => $plugin['path'],
            'Enabled' => $plugin['enabled'] ? 'Yes' : 'No',
            'Loaded' => $isLoaded ? 'Yes' : 'No',
            'Autoload' => $plugin['autoload'] ? 'Yes' : 'No',
        ];

        foreach ($info as $key => $value) {
            $this->io()->writeln("<info>{$key}:</info> {$value}");
        }

        // Show dependencies if any
        if (isset($metadata['requires'])) {
            $this->io()->section('Dependencies');
            foreach ($metadata['requires'] as $dep => $version) {
                $this->io()->writeln("  • {$dep}: {$version}");
            }
        }

        // Show commands if any
        if (isset($metadata['commands']) && !empty($metadata['commands'])) {
            $this->io()->section('Commands');
            foreach ($metadata['commands'] as $command) {
                $this->io()->writeln("  • {$command}");
            }
        }

        return Result::success($this);
    }

    /**
     * Create a new plugin from template
     *
     * @command plugin:create
     * @aliases plugin:new
     *
     * @param string $name Plugin name (e.g., my-plugin)
     * @option string $path Plugin directory path (default: .rover/plugins)
     * @option string $author Author name
     * @option string $description Plugin description
     */
    public function create(
        string $name,
        array $options = [
            'path' => '.rover/plugins',
            'author' => null,
            'description' => null,
        ]
    ): Result {
        $pluginPath = $options['path'] . '/' . $name;

        if (file_exists($pluginPath)) {
            $this->io()->error("Plugin directory already exists: $pluginPath");
            return Result::error($this);
        }

        $this->io()->title("Creating plugin: $name");

        // Create plugin directory structure
        $directories = [
            $pluginPath,
            $pluginPath . '/src',
            $pluginPath . '/src/Commands',
        ];

        foreach ($directories as $dir) {
            if (!mkdir($dir, 0755, true)) {
                $this->io()->error("Failed to create directory: $dir");
                return Result::error($this);
            }
        }

        // Create plugin.json
        $this->createPluginMetadata($pluginPath, $name, $options);

        // Create bootstrap.php
        $this->createBootstrapFile($pluginPath, $name);

        // Create example plugin class
        $this->createPluginClass($pluginPath, $name);

        // Create example command
        $this->createExampleCommand($pluginPath, $name);

        // Create README.md
        $this->createPluginReadme($pluginPath, $name, $options);

        $this->io()->success("Plugin created successfully at: $pluginPath");
        $this->io()->note([
            'Next steps:',
            '1. Edit the plugin class in src/Plugin.php',
            '2. Add your custom commands in src/Commands/',
            '3. Update plugin.json with your metadata',
            '4. Run: rover plugin:validate ' . $name,
        ]);

        return Result::success($this);
    }

    /**
     * Enable a plugin
     *
     * @command plugin:enable
     *
     * @param string $name Plugin name
     */
    public function enable(string $name): Result
    {
        $pluginManager = PluginManager::getInstance();

        if (!$pluginManager->getPlugin($name)) {
            $this->io()->error("Plugin not found: $name");
            return Result::error($this);
        }

        if ($pluginManager->enablePlugin($name)) {
            $this->io()->success("Plugin enabled: $name");

            // Try to load it
            if ($pluginManager->loadPlugin($name)) {
                $this->io()->note("Plugin loaded successfully");
            }

            return Result::success($this);
        }

        $this->io()->error("Failed to enable plugin: $name");
        return Result::error($this);
    }

    /**
     * Disable a plugin
     *
     * @command plugin:disable
     *
     * @param string $name Plugin name
     */
    public function disable(string $name): Result
    {
        $pluginManager = PluginManager::getInstance();

        if (!$pluginManager->getPlugin($name)) {
            $this->io()->error("Plugin not found: $name");
            return Result::error($this);
        }

        if ($pluginManager->disablePlugin($name)) {
            $this->io()->success("Plugin disabled: $name");
            $this->io()->note("Restart Rover to unload the plugin");
            return Result::success($this);
        }

        $this->io()->error("Failed to disable plugin: $name");
        return Result::error($this);
    }

    /**
     * Validate plugin structure
     *
     * @command plugin:validate
     *
     * @param string $name Plugin name or path
     */
    public function validate(string $name): Result
    {
        $pluginManager = PluginManager::getInstance();

        // Check if it's a plugin name or path
        $path = $name;
        if ($plugin = $pluginManager->getPlugin($name)) {
            $path = $plugin['path'];
        }

        if (!is_dir($path)) {
            $this->io()->error("Plugin directory not found: $path");
            return Result::error($this);
        }

        $this->io()->title("Validating plugin: $name");

        $errors = $pluginManager->validatePlugin($path);

        if (empty($errors)) {
            $this->io()->success("Plugin validation passed!");
            return Result::success($this);
        }

        $this->io()->error("Plugin validation failed:");
        foreach ($errors as $error) {
            $this->io()->writeln("  • $error");
        }

        return Result::error($this);
    }

    /**
     * List available plugin hooks
     *
     * @command plugin:hooks
     */
    public function hooks(): Result
    {
        $pluginManager = PluginManager::getInstance();
        $hooks = $pluginManager->getAvailableHooks();

        $this->io()->title('Available Plugin Hooks');

        $rows = [];
        foreach ($hooks as $hook => $description) {
            $rows[] = [$hook, $description];
        }

        $this->io()->table(['Hook', 'Description'], $rows);

        $this->io()->note([
            'Use these hooks in your plugin to respond to Rover events.',
            'Example: $this->registerHook(\'before_command\', function($data) { ... });',
        ]);

        return Result::success($this);
    }

    /**
     * Create plugin metadata file
     */
    private function createPluginMetadata(string $path, string $name, array $options): void
    {
        $metadata = [
            'name' => $name,
            'version' => '1.0.0',
            'description' => $options['description'] ?? "Custom Rover plugin: $name",
            'author' => $options['author'] ?? 'Your Name',
            'license' => 'MIT',
            'enabled' => true,
            'autoload' => true,
            'requires' => [
                'php' => '>=8.1',
                'rover' => '>=1.0.0',
            ],
            'commands' => [],
        ];

        file_put_contents(
            $path . '/plugin.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Create bootstrap file
     */
    private function createBootstrapFile(string $path, string $name): void
    {
        $className = $this->toPascalCase($name);

        $content = <<<PHP
<?php

/**
 * Plugin Bootstrap File
 *
 * This file is loaded when the plugin is activated.
 * Initialize your plugin here.
 */

require_once __DIR__ . '/src/Plugin.php';

// Initialize the plugin
new {$className}Plugin();

PHP;

        file_put_contents($path . '/bootstrap.php', $content);
    }

    /**
     * Create plugin class
     */
    private function createPluginClass(string $path, string $name): void
    {
        $className = $this->toPascalCase($name);

        $content = <<<PHP
<?php

use Rover\Plugin\BasePlugin;

/**
 * {$className} Plugin
 */
class {$className}Plugin extends BasePlugin
{
    public function boot(): void
    {
        \$this->name = '{$name}';
        \$this->version = '1.0.0';
        \$this->description = 'Custom plugin for {$name}';

        // Register commands
        \$this->registerCommand({$className}Commands::class);

        // Register hooks
        \$this->registerHook('before_command', function(\$data) {
            // Do something before any command runs
            \$this->log('Command executed: ' . (\$data['command'] ?? 'unknown'));
        });

        // You can register more hooks here
        // Available hooks: plugin_loaded, before_command, after_command,
        // project_init, backup_created, migration_run, test_completed,
        // deployment_started, deployment_completed
    }
}

PHP;

        file_put_contents($path . '/src/Plugin.php', $content);
    }

    /**
     * Create example command
     */
    private function createExampleCommand(string $path, string $name): void
    {
        $className = $this->toPascalCase($name);

        $content = <<<PHP
<?php

use Rover\Robo\Plugin\Commands\BaseCommand;
use Robo\Result;

/**
 * Custom commands for {$className}
 */
class {$className}Commands extends BaseCommand
{
    /**
     * Example command
     *
     * @command {$name}:hello
     *
     * @param string \$name Name to greet
     */
    public function hello(string \$name = 'World'): Result
    {
        \$this->io()->success("Hello, \$name! This is a custom {$className} command.");

        // You can use all BaseCommand utilities:
        // - \$this->isLaravelProject()
        // - \$this->artisan('command')
        // - \$this->hasPackage('package/name')
        // - \$this->success('message')
        // - \$this->error('message')

        return Result::success(\$this);
    }

    /**
     * Another example command
     *
     * @command {$name}:status
     */
    public function status(): Result
    {
        \$this->io()->title('{$className} Plugin Status');

        \$this->io()->writeln('Plugin is active and working!');

        if (\$this->isLaravelProject()) {
            \$this->io()->writeln('Laravel project detected');
            \$version = \$this->getLaravelVersion();
            \$this->io()->writeln("Laravel version: \$version");
        }

        return Result::success(\$this);
    }
}

PHP;

        file_put_contents($path . '/src/Commands/' . $className . 'Commands.php', $content);
    }

    /**
     * Create plugin README
     */
    private function createPluginReadme(string $path, string $name, array $options): void
    {
        $className = $this->toPascalCase($name);
        $author = $options['author'] ?? 'Your Name';
        $description = $options['description'] ?? "Custom Rover plugin for $name functionality";

        $content = <<<MD
# {$className} Plugin

{$description}

## Installation

This plugin is installed in your Rover plugins directory and will be automatically discovered.

## Commands

### `rover {$name}:hello [name]`

Greet someone with a custom message.

Example:
```bash
rover {$name}:hello John
```

### `rover {$name}:status`

Show the plugin status and Laravel project information.

Example:
```bash
rover {$name}:status
```

## Configuration

Add configuration to your `rover.yml`:

```yaml
plugins:
  {$name}:
    enabled: true
    # Add your custom configuration here
    option1: value1
    option2: value2
```

Access configuration in your plugin:
```php
\$value = \$this->getConfig('option1');
```

## Hooks

This plugin registers the following hooks:

- `before_command` - Logs every command execution

## Development

To modify this plugin:

1. Edit `src/Plugin.php` to change plugin behavior
2. Add new commands in `src/Commands/`
3. Update `plugin.json` with new metadata
4. Run `rover plugin:validate {$name}` to check structure

## Author

{$author}

## License

MIT

MD;

        file_put_contents($path . '/README.md', $content);
    }

    /**
     * Convert kebab-case to PascalCase
     */
    private function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
}
