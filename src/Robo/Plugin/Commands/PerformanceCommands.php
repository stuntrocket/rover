<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Performance profiling and optimization commands
 */
class PerformanceCommands extends BaseCommand
{
    /**
     * Profile application performance
     *
     * @command rover:profile
     * @param string|null $url URL or route to profile
     */
    public function profile(?string $url = null): Result
    {
        $this->requireLaravelProject();

        if (!$url) {
            $url = '/';
        }

        $this->info("Profiling: $url");
        $this->say('');

        // Check if app is running
        $baseUrl = $this->getAppUrl();

        if (!$baseUrl) {
            $this->error('Could not determine APP_URL from .env');
            return new ResultData(1, "");
        }

        $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');

        $this->say("Testing URL: $fullUrl");
        $this->say('');

        // Run multiple requests and measure
        $requests = 10;
        $times = [];

        for ($i = 1; $i <= $requests; $i++) {
            $start = microtime(true);

            $result = @file_get_contents($fullUrl);

            $end = microtime(true);
            $time = ($end - $start) * 1000; // Convert to ms

            $times[] = $time;

            $this->say("Request $i: " . round($time, 2) . " ms");
        }

        // Calculate statistics
        $this->say('');
        $this->info('Statistics:');

        $avg = array_sum($times) / count($times);
        $min = min($times);
        $max = max($times);

        $this->say("  Average: " . round($avg, 2) . " ms");
        $this->say("  Minimum: " . round($min, 2) . " ms");
        $this->say("  Maximum: " . round($max, 2) . " ms");

        // Performance rating
        $this->say('');
        if ($avg < 100) {
            $this->success('✓ Excellent performance!');
        } elseif ($avg < 300) {
            $this->info('✓ Good performance');
        } elseif ($avg < 1000) {
            $this->warning('⚠ Acceptable performance (consider optimization)');
        } else {
            $this->error('✗ Slow performance (optimization needed)');
        }

        return new ResultData(0, "");
    }

    /**
     * Detect N+1 query problems
     *
     * @command rover:n+1
     */
    public function detectNPlusOne(): Result
    {
        $this->requireLaravelProject();

        $this->info('Checking for N+1 query issues...');
        $this->say('');

        // Check if Telescope is installed
        if ($this->hasPackage('laravel/telescope')) {
            $this->say('✓ Telescope is installed');
            $this->info('Use Telescope to detect N+1 queries:');
            $this->say('  1. Visit /telescope/queries');
            $this->say('  2. Look for duplicate queries');
            $this->say('  3. Check "Slow Queries" section');
            $this->say('');
        } else {
            $this->warning('Telescope not installed');
            $this->info('Install Telescope for advanced query monitoring:');
            $this->say('  composer require laravel/telescope --dev');
            $this->say('  php artisan telescope:install');
            $this->say('');
        }

        // Check if Debugbar is installed
        if ($this->hasPackage('barryvdh/laravel-debugbar')) {
            $this->say('✓ Laravel Debugbar is installed');
            $this->info('Debugbar shows queries in the bottom toolbar');
            $this->say('');
        } else {
            $this->info('Install Laravel Debugbar for query monitoring:');
            $this->say('  composer require barryvdh/laravel-debugbar --dev');
            $this->say('');
        }

        // Look for common N+1 patterns in code
        $this->say('Scanning for common N+1 patterns...');

        $controllers = glob('app/Http/Controllers/*.php');
        $potentialIssues = [];

        foreach ($controllers as $controller) {
            $content = file_get_contents($controller);

            // Look for loops with model access
            if (preg_match_all('/@?foreach.*?as.*?\$(\w+).*?{.*?\$\1->(\w+).*?}/s', $content, $matches)) {
                $potentialIssues[] = basename($controller);
            }
        }

        if (!empty($potentialIssues)) {
            $this->warning('Potential N+1 issues found in:');
            foreach ($potentialIssues as $file) {
                $this->say("  - $file");
            }
            $this->say('');
            $this->info('Review these files and consider using eager loading:');
            $this->say('  $users = User::with(\'posts\')->get();');
        } else {
            $this->success('✓ No obvious N+1 patterns detected');
        }

        return new ResultData(0, "");
    }

    /**
     * Benchmark database queries
     *
     * @command rover:benchmark
     */
    public function benchmark(): Result
    {
        $this->requireLaravelProject();

        $this->info('Database Benchmark:');
        $this->say('');

        // Simple query benchmark
        $this->say('Running benchmark queries...');

        $results = [];

        // Test 1: Simple select
        $start = microtime(true);
        shell_exec('php artisan tinker --execute="DB::table(\'users\')->count();" 2>&1');
        $end = microtime(true);
        $results['Simple query'] = ($end - $start) * 1000;

        // Test 2: Connection test
        $start = microtime(true);
        $this->artisan('db:show', []);
        $end = microtime(true);
        $results['DB connection'] = ($end - $start) * 1000;

        // Display results
        foreach ($results as $test => $time) {
            $this->say("  $test: " . round($time, 2) . " ms");
        }

        $this->say('');
        $avgTime = array_sum($results) / count($results);

        if ($avgTime < 100) {
            $this->success('✓ Database performance is excellent');
        } elseif ($avgTime < 500) {
            $this->info('✓ Database performance is good');
        } else {
            $this->warning('⚠ Database performance may need optimization');
        }

        return new ResultData(0, "");
    }

    /**
     * Cache warming
     *
     * @command rover:cache:warm
     */
    public function warmCache(): Result
    {
        $this->requireLaravelProject();

        $this->info('Warming application caches...');

        // Config cache
        $this->say('Caching configuration...');
        $this->artisan('config:cache');

        // Route cache
        $this->say('Caching routes...');
        $this->artisan('route:cache');

        // View cache
        $this->say('Caching views...');
        $this->artisan('view:cache');

        // Event cache
        $this->say('Caching events...');
        $this->artisan('event:cache');

        $this->say('');
        $this->success('✓ Caches warmed!');
        $this->info('Application is ready for optimal performance');

        return new ResultData(0, "");
    }

    /**
     * Show application metrics
     *
     * @command rover:metrics
     */
    public function metrics(): Result
    {
        $this->requireLaravelProject();

        $this->info('Application Metrics:');
        $this->say('');

        // PHP version
        $this->say('PHP Version: ' . PHP_VERSION);

        // Laravel version
        $version = $this->getLaravelVersion();
        if ($version) {
            $this->say('Laravel Version: ' . $version);
        }

        // Memory usage
        $this->say('');
        $this->say('Resource Usage:');

        // Check cache sizes
        if (is_dir('bootstrap/cache')) {
            $cacheSize = $this->getDirectorySize('bootstrap/cache');
            $this->say('  Bootstrap cache: ' . $this->formatBytes($cacheSize));
        }

        if (is_dir('storage/framework/cache')) {
            $cacheSize = $this->getDirectorySize('storage/framework/cache');
            $this->say('  Framework cache: ' . $this->formatBytes($cacheSize));
        }

        // Log size
        if (file_exists('storage/logs/laravel.log')) {
            $logSize = filesize('storage/logs/laravel.log');
            $this->say('  Log file: ' . $this->formatBytes($logSize));
        }

        // Database size (MySQL only)
        $this->say('');
        $this->say('Database:');

        $dbConfig = $this->getDatabaseConfig();
        if ($dbConfig && $dbConfig['connection'] === 'mysql') {
            $size = shell_exec(sprintf(
                'mysql -h%s -P%s -u%s %s -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.TABLES WHERE table_schema = \'%s\';" -s -N',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['username'],
                $dbConfig['password'] ? '-p' . $dbConfig['password'] : '',
                $dbConfig['database']
            ));

            if ($size) {
                $this->say('  Database size: ' . trim($size) . ' MB');
            }
        }

        return new ResultData(0, "");
    }

    /**
     * Get app URL from .env
     */
    protected function getAppUrl(): ?string
    {
        if (!file_exists('.env')) {
            return null;
        }

        $env = file_get_contents('.env');

        if (preg_match('/^APP_URL=(.*)$/m', $env, $matches)) {
            return trim($matches[1], '"\'');
        }

        return 'http://localhost';
    }

    /**
     * Get database configuration
     */
    protected function getDatabaseConfig(): ?array
    {
        if (!file_exists('.env')) {
            return null;
        }

        $env = file_get_contents('.env');
        $config = [];

        $patterns = [
            'connection' => '/^DB_CONNECTION=(.*)$/m',
            'host' => '/^DB_HOST=(.*)$/m',
            'port' => '/^DB_PORT=(.*)$/m',
            'database' => '/^DB_DATABASE=(.*)$/m',
            'username' => '/^DB_USERNAME=(.*)$/m',
            'password' => '/^DB_PASSWORD=(.*)$/m',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $env, $matches)) {
                $config[$key] = trim($matches[1], '"\'');
            }
        }

        return !empty($config) ? $config : null;
    }

    /**
     * Get directory size
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;

        if (!is_dir($directory)) {
            return 0;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Format bytes
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
