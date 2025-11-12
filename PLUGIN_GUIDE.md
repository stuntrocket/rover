# Rover Plugin Development Guide

This guide covers everything you need to know about creating, managing, and distributing Rover plugins.

## Table of Contents

- [Overview](#overview)
- [Quick Start](#quick-start)
- [Plugin Architecture](#plugin-architecture)
- [Creating Plugins](#creating-plugins)
- [Commands](#commands)
- [Hooks System](#hooks-system)
- [Configuration](#configuration)
- [Best Practices](#best-practices)
- [Distribution](#distribution)
- [Examples](#examples)

## Overview

The Rover plugin system allows you to extend Rover with custom commands and functionality specific to your Laravel project or organization. Plugins are:

- **Project-specific** - Keep custom commands with your project
- **Reusable** - Share plugins across projects or teams
- **Integrated** - Full access to Rover's BaseCommand utilities
- **Hook-driven** - Respond to Rover events
- **Configurable** - Plugin-specific settings in `rover.yml`

## Quick Start

Create a new plugin in under a minute:

```bash
# 1. Create the plugin
vendor/bin/robo rover:plugin:create my-plugin

# 2. Edit the generated files
# - .rover/plugins/my-plugin/src/Plugin.php
# - .rover/plugins/my-plugin/src/Commands/MyPluginCommands.php

# 3. Use your plugin
vendor/bin/robo my-plugin:hello World
```

## Plugin Architecture

### Directory Structure

```
.rover/plugins/my-plugin/
├── plugin.json          # Plugin metadata
├── bootstrap.php        # Bootstrap file (entry point)
├── README.md           # Documentation
└── src/
    ├── Plugin.php      # Main plugin class
    └── Commands/       # Command classes
        └── MyPluginCommands.php
```

### Plugin Lifecycle

1. **Discovery** - PluginManager scans plugin directories
2. **Registration** - Plugins are registered with their metadata
3. **Loading** - Enabled plugins are loaded via bootstrap.php
4. **Initialization** - Plugin's `boot()` method is called
5. **Command Registration** - Commands are registered with Robo
6. **Hook Registration** - Hooks are registered with PluginManager

### Search Paths

Rover searches for plugins in these locations (highest to lowest priority):

1. `.rover/plugins/` - Project-specific (recommended)
2. `rover/plugins/` - Alternative project location
3. `[rover-root]/plugins/` - Global Rover plugins

## Creating Plugins

### Using the Generator

The easiest way to create a plugin:

```bash
vendor/bin/robo rover:plugin:create my-plugin \
    --author="Your Name" \
    --description="Custom functionality for my project"
```

### Manual Creation

If you prefer to create plugins manually:

#### 1. Create plugin.json

```json
{
    "name": "my-plugin",
    "version": "1.0.0",
    "description": "Custom Rover plugin",
    "author": "Your Name",
    "license": "MIT",
    "enabled": true,
    "autoload": true,
    "requires": {
        "php": ">=8.1",
        "rover": ">=1.0.0"
    },
    "commands": [
        "my-plugin:command1",
        "my-plugin:command2"
    ]
}
```

#### 2. Create bootstrap.php

```php
<?php

require_once __DIR__ . '/src/Plugin.php';
require_once __DIR__ . '/src/Commands/MyPluginCommands.php';

// Initialize the plugin
new MyPlugin();
```

#### 3. Create Plugin Class

```php
<?php

use Rover\Plugin\BasePlugin;

class MyPlugin extends BasePlugin
{
    public function boot(): void
    {
        $this->name = 'my-plugin';
        $this->version = '1.0.0';
        $this->description = 'Custom plugin';

        // Register commands
        $this->registerCommand(MyPluginCommands::class);

        // Register hooks
        $this->registerHook('before_command', [$this, 'onBeforeCommand']);
    }

    public function onBeforeCommand($data)
    {
        $this->log('Command starting...');
    }
}
```

## Commands

### Creating Commands

Commands extend `BaseCommand` and have access to all Rover utilities:

```php
<?php

use Rover\Robo\Plugin\Commands\BaseCommand;
use Robo\Result;

class MyPluginCommands extends BaseCommand
{
    /**
     * Process data with custom logic
     *
     * @command my-plugin:process
     *
     * @param string $input Input data
     * @option string $format Output format
     */
    public function process(string $input, array $options = ['format' => 'json']): Result
    {
        // Use Laravel detection
        if ($this->isLaravelProject()) {
            $version = $this->getLaravelVersion();
            $this->info("Processing in Laravel $version");
        }

        // Run artisan commands
        $this->artisan('cache:clear');

        // Check for packages
        if ($this->hasPackage('spatie/laravel-ray')) {
            $this->info('Ray debugging available');
        }

        // Trigger custom hooks
        $this->triggerHook('my_plugin_processed', [
            'input' => $input,
            'format' => $options['format']
        ]);

        $this->success('Processing complete!');

        return Result::success($this);
    }
}
```

### Available BaseCommand Methods

Your commands have access to:

```php
// Laravel Detection
$this->isLaravelProject()           // Check if in Laravel project
$this->requireLaravelProject()      // Exit if not Laravel
$this->getLaravelVersion()          // Get Laravel version string

// Package Detection
$this->hasPackage('vendor/package') // Check if package installed
$this->hasPest()                    // Check if Pest installed
$this->hasPhpUnit()                 // Check if PHPUnit installed

// Artisan Commands
$this->artisan('migrate', ['--force' => true])

// System Commands
$this->commandExists('docker')      // Check if command available

// Output Methods
$this->success('Success message')
$this->error('Error message')
$this->info('Info message')
$this->warning('Warning message')
$this->io()->table($headers, $rows)
$this->io()->confirm('Continue?')

// Hook Triggers
$this->triggerHook('hook_name', ['key' => 'value'])

// Plugin Manager
$pluginManager = $this->getPluginManager()
```

### Command Documentation

Use PHPDoc annotations for automatic documentation:

```php
/**
 * Deploy the application to production
 *
 * This command handles the complete deployment process including
 * backups, migrations, and cache warming.
 *
 * @command my-plugin:deploy
 * @aliases deploy
 *
 * @param string $environment Target environment (staging|production)
 * @option bool $backup Create backup before deployment
 * @option bool $migrate Run migrations
 * @usage rover my-plugin:deploy production --backup --migrate
 */
public function deploy(string $environment, array $options = [
    'backup' => true,
    'migrate' => true
]): Result
```

## Hooks System

Hooks allow plugins to respond to Rover events without modifying core commands.

### Available Hooks

```php
// Core hooks
'plugin_loaded'           // When plugin is loaded
'before_command'          // Before any command executes
'after_command'           // After any command completes

// Project hooks
'project_init'            // New project initialized

// Database hooks
'backup_created'          // After database backup
'migration_run'           // After migrations run

// Testing hooks
'test_completed'          // After tests complete

// Deployment hooks
'deployment_started'      // Deployment begins
'deployment_completed'    // Deployment finishes
```

### Registering Hooks

In your Plugin class:

```php
public function boot(): void
{
    // Simple callback
    $this->registerHook('before_command', function($data) {
        $this->log("Command: " . ($data['command'] ?? 'unknown'));
    });

    // Method callback
    $this->registerHook('test_completed', [$this, 'onTestCompleted']);

    // Multiple hooks
    $hooks = ['backup_created', 'migration_run'];
    foreach ($hooks as $hook) {
        $this->registerHook($hook, [$this, 'logEvent']);
    }
}

public function onTestCompleted($data)
{
    $result = $data['result'] ?? 'unknown';
    $this->log("Tests completed: $result");

    // Send notification
    if ($result === 'failed') {
        $this->sendSlackNotification('Tests failed!');
    }
}
```

### Custom Hooks

Trigger your own hooks from commands:

```php
// In your command
$this->triggerHook('my_plugin_custom_event', [
    'user' => auth()->user(),
    'action' => 'deployed',
    'timestamp' => now()
]);

// Other plugins can listen
$this->registerHook('my_plugin_custom_event', function($data) {
    // React to event
});
```

## Configuration

### Plugin Configuration

Add plugin settings to `rover.yml`:

```yaml
plugins:
  # Enable/disable plugins
  enabled:
    - my-plugin
    - another-plugin

  # Plugin-specific configuration
  my-plugin:
    enabled: true
    slack_webhook: https://hooks.slack.com/...
    deployment:
      backup: true
      migrate: true
      environments:
        - staging
        - production
    notifications:
      channels:
        - slack
        - email
```

### Accessing Configuration

In your plugin:

```php
// Get config value with default
$webhook = $this->getConfig('slack_webhook', null);

// Nested configuration
$environments = $this->getConfig('deployment.environments', []);

// Check if config exists
if ($this->getConfig('notifications.channels')) {
    // Send notifications
}

// In commands, use Config class directly
use Rover\Config\Config;

$config = Config::getInstance();
$value = $config->get('plugins.my-plugin.setting');
```

## Best Practices

### Naming Conventions

```bash
# Plugin names: kebab-case
my-plugin
acme-deploy-tool

# Command namespaces: plugin-name:action
my-plugin:deploy
my-plugin:status
acme-deploy:production

# Class names: PascalCase
MyPlugin
MyPluginCommands
AcmeDeployPlugin
```

### Error Handling

```php
public function deploy(string $env): Result
{
    try {
        // Validate environment
        if (!in_array($env, ['staging', 'production'])) {
            $this->error("Invalid environment: $env");
            return Result::error($this);
        }

        // Check preconditions
        if (!$this->isLaravelProject()) {
            $this->error('Not a Laravel project');
            return Result::error($this);
        }

        // Perform deployment
        $result = $this->performDeployment($env);

        if ($result->wasSuccessful()) {
            $this->success('Deployment complete!');
            return Result::success($this);
        }

        $this->error('Deployment failed');
        return Result::error($this);

    } catch (\Exception $e) {
        $this->error('Deployment error: ' . $e->getMessage());
        $this->log($e->getTraceAsString(), 'error');
        return Result::error($this);
    }
}
```

### Security

```php
// Validate inputs
public function deleteData(string $table): Result
{
    // Whitelist allowed tables
    $allowed = ['cache', 'sessions', 'jobs'];
    if (!in_array($table, $allowed)) {
        $this->error("Table not allowed: $table");
        return Result::error($this);
    }

    // Require confirmation for destructive operations
    if (!$this->io()->confirm("Delete all data from $table?", false)) {
        $this->info('Cancelled');
        return Result::cancelled($this);
    }

    // Check environment
    if (app()->environment('production')) {
        $this->error('Cannot run in production');
        return Result::error($this);
    }

    // Proceed...
}
```

### Testing Plugins

```php
// Add test structure to your plugin
.rover/plugins/my-plugin/
├── tests/
│   ├── Unit/
│   │   └── PluginTest.php
│   └── Feature/
│       └── CommandTest.php
└── phpunit.xml
```

Create tests:

```php
<?php

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function test_plugin_loads()
    {
        $plugin = new MyPlugin();
        $this->assertEquals('my-plugin', $plugin->getName());
    }

    public function test_commands_registered()
    {
        $plugin = new MyPlugin();
        $commands = $plugin->getCommands();
        $this->assertContains(MyPluginCommands::class, $commands);
    }
}
```

## Distribution

### Sharing Within Organization

1. **Git Repository**:
```bash
# Add to .gitignore
!.rover/plugins/my-plugin

# Commit and push
git add .rover/plugins/my-plugin
git commit -m "Add my-plugin"
git push
```

2. **Documentation**:
```markdown
# Project Setup

After cloning:
1. `composer install`
2. `vendor/bin/robo rover:plugin:validate my-plugin`
3. `vendor/bin/robo my-plugin:status`
```

### Distributing as Package

Create a composer package:

```json
{
    "name": "acme/rover-deploy-plugin",
    "description": "Deployment plugin for Rover",
    "type": "library",
    "require": {
        "php": ">=8.1"
    },
    "autoload": {
        "psr-4": {
            "Acme\\RoverDeploy\\": "src/"
        }
    },
    "extra": {
        "rover-plugin": {
            "provider": "Acme\\RoverDeploy\\DeployPlugin"
        }
    }
}
```

Users can install via Composer:

```bash
composer require acme/rover-deploy-plugin --dev
```

## Examples

### Deployment Plugin

```php
class DeployPlugin extends BasePlugin
{
    public function boot(): void
    {
        $this->name = 'deploy';
        $this->version = '1.0.0';
        $this->registerCommand(DeployCommands::class);

        $this->registerHook('deployment_started', function($data) {
            $this->sendSlackNotification('🚀 Deployment started');
        });

        $this->registerHook('deployment_completed', function($data) {
            $this->sendSlackNotification('✅ Deployment completed');
        });
    }

    private function sendSlackNotification($message)
    {
        $webhook = $this->getConfig('slack_webhook');
        if ($webhook) {
            // Send to Slack
        }
    }
}
```

### Code Generator Plugin

```php
class GeneratorCommands extends BaseCommand
{
    /**
     * @command generate:service
     */
    public function service(string $name): Result
    {
        $this->requireLaravelProject();

        $path = app_path("Services/{$name}.php");

        if (file_exists($path)) {
            $this->error("Service already exists: $name");
            return Result::error($this);
        }

        $stub = $this->getStub('service');
        $content = str_replace('{{name}}', $name, $stub);

        file_put_contents($path, $content);

        $this->success("Service created: $path");
        return Result::success($this);
    }
}
```

### Monitoring Plugin

```php
class MonitorPlugin extends BasePlugin
{
    public function boot(): void
    {
        $this->registerHook('after_command', [$this, 'trackCommand']);
    }

    public function trackCommand($data)
    {
        $command = $data['command'] ?? 'unknown';
        $duration = $data['duration'] ?? 0;

        // Log to monitoring service
        $this->log("Command $command took {$duration}ms");

        // Alert if slow
        if ($duration > 5000) {
            $this->alert("Slow command detected: $command");
        }
    }
}
```

## Validation

Always validate your plugin before distribution:

```bash
# Validate structure
vendor/bin/robo rover:plugin:validate my-plugin

# Test commands
vendor/bin/robo my-plugin:test

# Check documentation
vendor/bin/robo rover:plugin:info my-plugin
```

## Support

- Check the example plugin: `plugins/example/`
- List available hooks: `vendor/bin/robo rover:plugin:hooks`
- View plugin info: `vendor/bin/robo rover:plugin:info <name>`

## Contributing Plugins

If you create a useful plugin, consider:

1. Publishing to Packagist
2. Adding to Rover's plugin directory
3. Sharing with the Laravel community

---

**Happy Plugin Development!** 🔌
