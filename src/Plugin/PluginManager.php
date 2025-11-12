<?php

namespace Rover\Plugin;

use Rover\Config\Config;

/**
 * Plugin Manager - Discovers and loads Rover plugins
 */
class PluginManager
{
    private static ?PluginManager $instance = null;
    private array $plugins = [];
    private array $loadedPlugins = [];
    private array $hooks = [];

    /**
     * Plugin search paths (in order of priority)
     */
    private array $pluginPaths = [
        '.rover/plugins',           // Project-specific plugins (highest priority)
        'rover/plugins',            // Alternative project location
        __DIR__ . '/../../plugins', // Global Rover plugins
    ];

    private function __construct()
    {
        $this->discoverPlugins();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Discover all available plugins
     */
    private function discoverPlugins(): void
    {
        foreach ($this->pluginPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $pluginDirs = glob($path . '/*', GLOB_ONLYDIR);

            foreach ($pluginDirs as $pluginDir) {
                $this->registerPluginFromDirectory($pluginDir);
            }
        }

        // Load enabled plugins from config
        $this->loadEnabledPlugins();
    }

    /**
     * Register a plugin from a directory
     */
    private function registerPluginFromDirectory(string $dir): void
    {
        $pluginFile = $dir . '/plugin.json';

        if (!file_exists($pluginFile)) {
            return;
        }

        $metadata = json_decode(file_get_contents($pluginFile), true);

        if (!$metadata || !isset($metadata['name'])) {
            return;
        }

        $pluginName = $metadata['name'];

        $this->plugins[$pluginName] = [
            'name' => $pluginName,
            'path' => $dir,
            'metadata' => $metadata,
            'enabled' => $metadata['enabled'] ?? true,
            'autoload' => $metadata['autoload'] ?? true,
        ];
    }

    /**
     * Load enabled plugins
     */
    private function loadEnabledPlugins(): void
    {
        $config = Config::getInstance();
        $enabledPlugins = $config->get('plugins.enabled', []);

        foreach ($this->plugins as $name => $plugin) {
            // Check if plugin is enabled in config (if config exists)
            if (!empty($enabledPlugins) && !in_array($name, $enabledPlugins)) {
                continue;
            }

            // Check if plugin has autoload disabled
            if (!$plugin['autoload']) {
                continue;
            }

            // Check if plugin is marked as disabled
            if (!$plugin['enabled']) {
                continue;
            }

            $this->loadPlugin($name);
        }
    }

    /**
     * Load a specific plugin
     */
    public function loadPlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        if (isset($this->loadedPlugins[$name])) {
            return true; // Already loaded
        }

        $plugin = $this->plugins[$name];
        $bootstrapFile = $plugin['path'] . '/bootstrap.php';

        if (!file_exists($bootstrapFile)) {
            return false;
        }

        // Load the plugin
        require_once $bootstrapFile;

        $this->loadedPlugins[$name] = $plugin;

        // Trigger plugin loaded hook
        $this->triggerHook('plugin_loaded', ['plugin' => $name]);

        return true;
    }

    /**
     * Get all discovered plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get loaded plugins
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Get plugin metadata
     */
    public function getPlugin(string $name): ?array
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Check if plugin is loaded
     */
    public function isPluginLoaded(string $name): bool
    {
        return isset($this->loadedPlugins[$name]);
    }

    /**
     * Register a hook callback
     */
    public function registerHook(string $hook, callable $callback): void
    {
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }

        $this->hooks[$hook][] = $callback;
    }

    /**
     * Trigger a hook
     */
    public function triggerHook(string $hook, array $data = []): void
    {
        if (!isset($this->hooks[$hook])) {
            return;
        }

        foreach ($this->hooks[$hook] as $callback) {
            call_user_func($callback, $data);
        }
    }

    /**
     * Enable a plugin
     */
    public function enablePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->plugins[$name]['enabled'] = true;
        $this->updatePluginMetadata($name, ['enabled' => true]);

        return true;
    }

    /**
     * Disable a plugin
     */
    public function disablePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->plugins[$name]['enabled'] = false;
        $this->updatePluginMetadata($name, ['enabled' => false]);

        // Unload if currently loaded
        if (isset($this->loadedPlugins[$name])) {
            unset($this->loadedPlugins[$name]);
        }

        return true;
    }

    /**
     * Update plugin metadata file
     */
    private function updatePluginMetadata(string $name, array $updates): void
    {
        if (!isset($this->plugins[$name])) {
            return;
        }

        $pluginFile = $this->plugins[$name]['path'] . '/plugin.json';

        if (!file_exists($pluginFile)) {
            return;
        }

        $metadata = json_decode(file_get_contents($pluginFile), true);
        $metadata = array_merge($metadata, $updates);

        file_put_contents(
            $pluginFile,
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Update in-memory metadata
        $this->plugins[$name]['metadata'] = $metadata;
    }

    /**
     * Get available hooks
     */
    public function getAvailableHooks(): array
    {
        return [
            'plugin_loaded' => 'Triggered when a plugin is loaded',
            'before_command' => 'Triggered before any Rover command executes',
            'after_command' => 'Triggered after any Rover command executes',
            'project_init' => 'Triggered when initializing a new project',
            'backup_created' => 'Triggered after a backup is created',
            'migration_run' => 'Triggered after migrations are run',
            'test_completed' => 'Triggered after tests complete',
            'deployment_started' => 'Triggered when deployment starts',
            'deployment_completed' => 'Triggered when deployment completes',
        ];
    }

    /**
     * Validate plugin structure
     */
    public function validatePlugin(string $path): array
    {
        $errors = [];

        // Check if directory exists
        if (!is_dir($path)) {
            $errors[] = 'Plugin directory does not exist';
            return $errors;
        }

        // Check for plugin.json
        $pluginFile = $path . '/plugin.json';
        if (!file_exists($pluginFile)) {
            $errors[] = 'Missing plugin.json file';
        } else {
            $metadata = json_decode(file_get_contents($pluginFile), true);

            if (!$metadata) {
                $errors[] = 'Invalid plugin.json format';
            } else {
                // Validate required fields
                $required = ['name', 'version', 'description'];
                foreach ($required as $field) {
                    if (!isset($metadata[$field])) {
                        $errors[] = "Missing required field: $field";
                    }
                }
            }
        }

        // Check for bootstrap.php
        if (!file_exists($path . '/bootstrap.php')) {
            $errors[] = 'Missing bootstrap.php file';
        }

        // Check for README.md
        if (!file_exists($path . '/README.md')) {
            $errors[] = 'Missing README.md (recommended)';
        }

        return $errors;
    }
}
