<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Tasks;
use Robo\Result;
use Rover\Plugin\PluginManager;

/**
 * Base command class with Laravel detection and common utilities
 */
abstract class BaseCommand extends Tasks
{
    protected ?PluginManager $pluginManager = null;

    /**
     * Get plugin manager instance
     */
    protected function getPluginManager(): PluginManager
    {
        if ($this->pluginManager === null) {
            $this->pluginManager = PluginManager::getInstance();
        }

        return $this->pluginManager;
    }

    /**
     * Trigger a plugin hook
     */
    protected function triggerHook(string $hook, array $data = []): void
    {
        $this->getPluginManager()->triggerHook($hook, $data);
    }
    /**
     * Check if current directory is a Laravel project
     *
     * @return bool
     */
    protected function isLaravelProject(): bool
    {
        return file_exists('artisan') && file_exists('composer.json');
    }

    /**
     * Verify we're in a Laravel project or exit
     *
     * @return void
     */
    protected function requireLaravelProject(): void
    {
        if (!$this->isLaravelProject()) {
            $this->io()->error('Not a Laravel project! Please run this command from a Laravel project root.');
            exit(1);
        }
    }

    /**
     * Get the Laravel version
     *
     * @return string|null
     */
    protected function getLaravelVersion(): ?string
    {
        if (!$this->isLaravelProject()) {
            return null;
        }

        $composerJson = json_decode(file_get_contents('composer.json'), true);

        if (isset($composerJson['require']['laravel/framework'])) {
            return $composerJson['require']['laravel/framework'];
        }

        return null;
    }

    /**
     * Check if a command exists in the system
     *
     * @param string $command
     * @return bool
     */
    protected function commandExists(string $command): bool
    {
        $result = $this->taskExec("which $command")
            ->printOutput(false)
            ->run();

        return $result->wasSuccessful();
    }

    /**
     * Run an artisan command
     *
     * @param string $command
     * @param array $options
     * @return Result
     */
    protected function artisan(string $command, array $options = []): Result
    {
        $this->requireLaravelProject();

        $optionsString = '';
        foreach ($options as $key => $value) {
            if (is_bool($value) && $value) {
                $optionsString .= " --$key";
            } elseif (!is_bool($value)) {
                $optionsString .= " --$key=$value";
            }
        }

        return $this->taskExec("php artisan $command$optionsString")
            ->run();
    }

    /**
     * Check if Composer package is installed
     *
     * @param string $package
     * @return bool
     */
    protected function hasPackage(string $package): bool
    {
        if (!file_exists('composer.json')) {
            return false;
        }

        $composerJson = json_decode(file_get_contents('composer.json'), true);

        return isset($composerJson['require'][$package])
            || isset($composerJson['require-dev'][$package]);
    }

    /**
     * Check if Pest is installed
     *
     * @return bool
     */
    protected function hasPest(): bool
    {
        return $this->hasPackage('pestphp/pest');
    }

    /**
     * Check if PHPUnit is installed
     *
     * @return bool
     */
    protected function hasPhpUnit(): bool
    {
        return $this->hasPackage('phpunit/phpunit') || file_exists('vendor/bin/phpunit');
    }

    /**
     * Success message with formatting
     *
     * @param string $message
     * @return void
     */
    protected function success(string $message): void
    {
        $this->io()->success($message);
    }

    /**
     * Error message with formatting
     *
     * @param string $message
     * @return void
     */
    protected function error(string $message): void
    {
        $this->io()->error($message);
    }

    /**
     * Info message with formatting
     *
     * @param string $message
     * @return void
     */
    protected function info(string $message): void
    {
        $this->io()->note($message);
    }

    /**
     * Warning message with formatting
     *
     * @param string $message
     * @return void
     */
    protected function warning(string $message): void
    {
        $this->io()->warning($message);
    }
}
