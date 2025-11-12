# Rover CLI Helper Script

This directory contains helper scripts to make using Rover easier in your Laravel projects.

## `rover` Script

A convenient wrapper that automatically detects Laravel Sail and routes Rover commands appropriately.

### Installation

Copy this script to your Laravel project root:

```bash
cp vendor/stuntrocket/rover/bin/rover ./rover
chmod +x ./rover
```

Or create it manually in your Laravel project root with the following content:

```bash
#!/usr/bin/env bash

if [ -f ./vendor/bin/sail ]; then
    ./vendor/bin/sail bash -c "vendor/bin/robo $*"
else
    vendor/bin/robo "$@"
fi
```

Then make it executable:

```bash
chmod +x rover
```

### Usage

Once installed in your project root, you can use it like this:

```bash
# Instead of: vendor/bin/robo rover:clear
./rover rover:clear

# Instead of: vendor/bin/robo rover:test
./rover rover:test

# List all commands
./rover list

# Get help about Rover
./rover rover:about
```

### How It Works

- **With Sail**: If `vendor/bin/sail` exists, it runs commands through the Sail container
- **Without Sail**: If no Sail installation is detected, it runs `vendor/bin/robo` directly

This means you can use the same command regardless of your environment setup!

### Team Usage

You can commit this script to your repository so your entire team can use it:

```bash
git add rover
git commit -m "Add Rover CLI helper script"
```

Add it to your `.gitignore` exceptions if needed:

```
# .gitignore
/vendor/
!/rover
```
