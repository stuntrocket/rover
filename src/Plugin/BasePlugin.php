<?php

namespace Rover\Plugin;

use Rover\Robo\Plugin\Commands\BaseCommand;

/**
 * Base Plugin class - All plugins should extend this
 */
abstract class BasePlugin
{
    protected string $name;
    protected string $version;
    protected string $description;
    protected array $commands = [];
    protected PluginManager $pluginManager;

    public function __construct()
    {
        $this->pluginManager = PluginManager::getInstance();
        $this->boot();
    }

    /**
     * Boot the plugin - override this to register commands and hooks
     */
    abstract public function boot(): void;

    /**
     * Register a command class
     */
    protected function registerCommand(string $commandClass): void
    {
        if (!class_exists($commandClass)) {
            throw new \Exception("Command class does not exist: $commandClass");
        }

        if (!is_subclass_of($commandClass, BaseCommand::class)) {
            throw new \Exception("Command must extend BaseCommand: $commandClass");
        }

        $this->commands[] = $commandClass;
    }

    /**
     * Register a hook callback
     */
    protected function registerHook(string $hook, callable $callback): void
    {
        $this->pluginManager->registerHook($hook, $callback);
    }

    /**
     * Get plugin name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get plugin version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get plugin description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get registered commands
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get plugin configuration from rover.yml
     */
    protected function getConfig(string $key, $default = null)
    {
        return \Rover\Config\Config::getInstance()->get("plugins.{$this->name}.{$key}", $default);
    }

    /**
     * Helper method to log plugin messages
     */
    protected function log(string $message, string $level = 'info'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[{$timestamp}] [{$this->name}] [{$level}] {$message}");
    }
}
