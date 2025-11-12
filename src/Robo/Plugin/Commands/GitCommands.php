<?php

namespace Rover\Robo\Plugin\Commands;

use Robo\Result;
use Robo\ResultData;

/**
 * Git integration and hooks management
 */
class GitCommands extends BaseCommand
{
    /**
     * Install git hooks for quality checks
     *
     * @command rover:git:hooks
     * @aliases git:hooks
     */
    public function installHooks(): Result
    {
        $this->requireLaravelProject();

        if (!is_dir('.git')) {
            $this->error('Not a git repository!');
            $this->info('Run: git init');
            return new ResultData(1, "");
        }

        $this->info('Installing git hooks...');

        $hooksInstalled = [];

        // Install pre-commit hook
        if ($this->installPreCommitHook()) {
            $hooksInstalled[] = 'pre-commit';
        }

        // Install pre-push hook
        if ($this->installPrePushHook()) {
            $hooksInstalled[] = 'pre-push';
        }

        // Install commit-msg hook
        if ($this->installCommitMsgHook()) {
            $hooksInstalled[] = 'commit-msg';
        }

        if (!empty($hooksInstalled)) {
            $this->success('✓ Installed hooks: ' . implode(', ', $hooksInstalled));
            $this->info("\nThese hooks will now run automatically on git operations.");
        } else {
            $this->warning('No hooks were installed');
        }

        return new ResultData(0, "");
    }

    /**
     * Install pre-commit hook
     *
     * @return bool
     */
    protected function installPreCommitHook(): bool
    {
        $hookPath = '.git/hooks/pre-commit';
        $hookContent = <<<'BASH'
#!/bin/bash

echo "Running pre-commit checks..."

# Run Rover checks
vendor/bin/robo rover:lint --test --dirty

if [ $? -ne 0 ]; then
    echo "❌ Code style check failed!"
    echo "Run: vendor/bin/robo rover:fix"
    exit 1
fi

echo "✅ Pre-commit checks passed!"
exit 0

BASH;

        if (file_exists($hookPath)) {
            if (!$this->io()->confirm('pre-commit hook already exists. Overwrite?', false)) {
                return false;
            }
        }

        file_put_contents($hookPath, $hookContent);
        chmod($hookPath, 0755);

        return true;
    }

    /**
     * Install pre-push hook
     *
     * @return bool
     */
    protected function installPrePushHook(): bool
    {
        $hookPath = '.git/hooks/pre-push';
        $hookContent = <<<'BASH'
#!/bin/bash

echo "Running pre-push checks..."

# Run tests
if [ -f "vendor/bin/pest" ]; then
    vendor/bin/pest
elif [ -f "vendor/bin/phpunit" ]; then
    vendor/bin/phpunit
fi

if [ $? -ne 0 ]; then
    echo "❌ Tests failed!"
    echo "Fix the tests before pushing."
    exit 1
fi

echo "✅ Pre-push checks passed!"
exit 0

BASH;

        if (file_exists($hookPath)) {
            if (!$this->io()->confirm('pre-push hook already exists. Overwrite?', false)) {
                return false;
            }
        }

        file_put_contents($hookPath, $hookContent);
        chmod($hookPath, 0755);

        return true;
    }

    /**
     * Install commit-msg hook
     *
     * @return bool
     */
    protected function installCommitMsgHook(): bool
    {
        $hookPath = '.git/hooks/commit-msg';
        $hookContent = <<<'BASH'
#!/bin/bash

# Get the commit message
commit_msg=$(cat "$1")

# Check minimum length
if [ ${#commit_msg} -lt 10 ]; then
    echo "❌ Commit message too short (minimum 10 characters)"
    exit 1
fi

# Check for WIP commits on main/master
branch=$(git symbolic-ref --short HEAD)
if [[ "$branch" == "main" || "$branch" == "master" ]]; then
    if [[ $commit_msg =~ ^(WIP|wip|TODO|todo) ]]; then
        echo "❌ Cannot commit WIP/TODO to $branch branch"
        exit 1
    fi
fi

exit 0

BASH;

        if (file_exists($hookPath)) {
            if (!$this->io()->confirm('commit-msg hook already exists. Overwrite?', false)) {
                return false;
            }
        }

        file_put_contents($hookPath, $hookContent);
        chmod($hookPath, 0755);

        return true;
    }

    /**
     * Remove git hooks
     *
     * @command rover:git:hooks:remove
     */
    public function removeHooks(): Result
    {
        $this->requireLaravelProject();

        if (!is_dir('.git')) {
            $this->error('Not a git repository!');
            return new ResultData(1, "");
        }

        $this->info('Removing git hooks...');

        $hooks = ['pre-commit', 'pre-push', 'commit-msg'];
        $removed = [];

        foreach ($hooks as $hook) {
            $hookPath = ".git/hooks/$hook";
            if (file_exists($hookPath)) {
                unlink($hookPath);
                $removed[] = $hook;
            }
        }

        if (!empty($removed)) {
            $this->success('✓ Removed hooks: ' . implode(', ', $removed));
        } else {
            $this->info('No hooks to remove');
        }

        return new ResultData(0, "");
    }

    /**
     * Show git status for all Laravel projects
     *
     * @command rover:git:status-all
     */
    public function statusAll(): Result
    {
        $this->info('Checking git status for all projects...');

        $projects = $this->findLaravelProjects('.');

        if (empty($projects)) {
            $this->warning('No Laravel projects found');
            return new ResultData(0, "");
        }

        $this->say('');

        foreach ($projects as $project) {
            if (!is_dir("$project/.git")) {
                continue;
            }

            $this->say("📁 $project");

            // Get current branch
            $originalDir = getcwd();
            chdir($project);

            $branch = trim(shell_exec('git symbolic-ref --short HEAD 2>/dev/null'));
            if ($branch) {
                $this->say("  Branch: $branch");
            }

            // Get status
            $status = shell_exec('git status --porcelain');
            if (empty($status)) {
                $this->say("  Status: ✓ Clean");
            } else {
                $changes = count(explode("\n", trim($status)));
                $this->say("  Status: ⚠ $changes uncommitted change(s)");
            }

            // Check if ahead/behind remote
            $ahead = trim(shell_exec('git rev-list --count @{u}..HEAD 2>/dev/null'));
            $behind = trim(shell_exec('git rev-list --count HEAD..@{u} 2>/dev/null'));

            if ($ahead && $ahead !== '0') {
                $this->say("  ↑ $ahead commit(s) ahead");
            }
            if ($behind && $behind !== '0') {
                $this->say("  ↓ $behind commit(s) behind");
            }

            chdir($originalDir);
            $this->say('');
        }

        return new ResultData(0, "");
    }

    /**
     * Find Laravel projects helper
     *
     * @param string $directory
     * @return array
     */
    protected function findLaravelProjects(string $directory): array
    {
        $projects = [];
        $d = dir($directory);

        if (!$d) {
            return $projects;
        }

        while (false !== ($entry = $d->read())) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = "$directory/$entry";
            if (is_dir($path) && file_exists("$path/artisan")) {
                $projects[] = $entry;
            }
        }

        $d->close();
        sort($projects);

        return $projects;
    }

    /**
     * Create a .gitignore from Laravel template
     *
     * @command rover:git:gitignore
     */
    public function createGitignore(): Result
    {
        $this->requireLaravelProject();

        if (file_exists('.gitignore')) {
            if (!$this->io()->confirm('.gitignore already exists. Overwrite?', false)) {
                return Result::cancelled();
            }
        }

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
*.log
.DS_Store
Thumbs.db

GITIGNORE;

        file_put_contents('.gitignore', $gitignore);
        $this->success('✓ .gitignore created');

        return new ResultData(0, "");
    }
}
