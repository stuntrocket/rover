# Publishing Rover to Packagist

This guide covers how to publish Rover to Packagist and manage releases.

## Prerequisites

✅ **Complete** - composer.json is configured with:
- Correct package name (`stuntrocket/rover`)
- Descriptive metadata
- MIT license
- PHP 8.1+ requirement
- Proper PSR-4 autoloading

✅ **Complete** - LICENSE file (MIT)
✅ **Complete** - README.md with comprehensive documentation
✅ **Complete** - Git repository with commits

## Step-by-Step Publishing Process

### 1. Ensure GitHub Repository is Public

Your repository needs to be publicly accessible on GitHub. The URL should be:
```
https://github.com/stuntrocket/rover
```

**Action Required:**
- Push your code to GitHub (if not already done)
- Make sure the repository is public (not private)
- Verify the repository is accessible at the URL above

### 2. Create a Git Tag for the Release

Packagist uses Git tags to identify versions. Follow semantic versioning (MAJOR.MINOR.PATCH):

```bash
# Create and push the first version tag
git tag -a v1.0.0 -m "Initial release of Rover - Laravel development assistant

Features:
- 100+ commands for Laravel development
- Code quality enforcement (Pint, PHPStan)
- Database management and backups
- Multi-project management
- Package development tools
- Plugin system for extensibility
- Testing and performance tools
"

# Push the tag to GitHub
git push origin v1.0.0

# Or push all tags
git push --tags
```

**Version Guidelines:**
- `v1.0.0` - First stable release
- `v1.0.1` - Bug fixes (patch)
- `v1.1.0` - New features (minor)
- `v2.0.0` - Breaking changes (major)

### 3. Create a GitHub Account (if needed)

If you don't have one already:
- Go to https://github.com
- Sign up or log in

### 4. Create a Packagist Account

1. Go to https://packagist.org
2. Click "Sign in with GitHub"
3. Authorize Packagist to access your GitHub account
4. Complete your profile

### 5. Submit Your Package to Packagist

1. **Go to Packagist:**
   - Visit https://packagist.org/packages/submit

2. **Enter Repository URL:**
   ```
   https://github.com/stuntrocket/rover
   ```

3. **Click "Check":**
   - Packagist will analyze your repository
   - It will validate composer.json
   - It will check for tags/releases

4. **Click "Submit":**
   - Your package will be submitted for publication
   - It typically appears within minutes

### 6. Set Up GitHub Webhook (Automatic Updates)

After publishing, set up auto-updates when you push new tags:

1. **In Packagist:**
   - Go to your package: https://packagist.org/packages/stuntrocket/rover
   - Click your username → "Profile"
   - Copy your API token

2. **In GitHub:**
   - Go to your repository: https://github.com/stuntrocket/rover
   - Click "Settings" → "Webhooks" → "Add webhook"
   - Paste this URL:
     ```
     https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME
     ```
   - Content type: `application/json`
   - Secret: Your Packagist API token
   - Click "Add webhook"

Now whenever you push a new tag, Packagist automatically updates!

### 7. Verify Installation Works

Test that users can install your package:

```bash
# In a fresh Laravel project
composer require stuntrocket/rover --dev

# Verify installation
vendor/bin/robo list rover:

# Test a command
vendor/bin/robo rover:about
```

## Publishing Process Checklist

Before publishing, ensure:

- [ ] composer.json has all required fields
- [ ] LICENSE file exists
- [ ] README.md is comprehensive and up-to-date
- [ ] All code is committed and pushed to GitHub
- [ ] GitHub repository is public
- [ ] Git tag created (v1.0.0)
- [ ] Tag pushed to GitHub
- [ ] Packagist account created
- [ ] Package submitted to Packagist
- [ ] GitHub webhook configured
- [ ] Installation tested

## Managing Releases

### Creating New Releases

When you're ready to release a new version:

```bash
# 1. Update CHANGELOG (if you have one)
# 2. Update version references if needed
# 3. Commit all changes
git add -A
git commit -m "Prepare v1.1.0 release"

# 4. Create and push tag
git tag -a v1.1.0 -m "Release v1.1.0 - Description of changes"
git push origin main
git push origin v1.1.0

# 5. Packagist auto-updates (if webhook is set up)
# Otherwise, manually trigger update on Packagist
```

### Release Types

**Patch Release (1.0.x)** - Bug fixes only:
```bash
git tag -a v1.0.1 -m "Fix: Correct plugin loading issue"
git push origin v1.0.1
```

**Minor Release (1.x.0)** - New features, backward compatible:
```bash
git tag -a v1.1.0 -m "Feature: Add deployment automation commands"
git push origin v1.1.0
```

**Major Release (x.0.0)** - Breaking changes:
```bash
git tag -a v2.0.0 -m "Breaking: Require PHP 8.2, refactor plugin system"
git push origin v2.0.0
```

### Pre-releases

For testing before official release:

```bash
# Alpha
git tag -a v1.1.0-alpha.1 -m "Alpha release for testing"

# Beta
git tag -a v1.1.0-beta.1 -m "Beta release"

# Release Candidate
git tag -a v1.1.0-rc.1 -m "Release candidate"

git push origin --tags
```

Users can install pre-releases:
```bash
composer require stuntrocket/rover:1.1.0-beta.1 --dev
```

## Marketing Your Package

### 1. Update Repository Description

On GitHub, set the description to:
```
An opinionated Laravel development assistant for teams who value quality and standards
```

Add topics:
- laravel
- php
- cli
- devops
- testing
- automation
- code-quality

### 2. Create GitHub Release Notes

For each tag, create a GitHub Release:
1. Go to https://github.com/stuntrocket/rover/releases
2. Click "Draft a new release"
3. Choose the tag (v1.0.0)
4. Add release title: "Rover v1.0.0 - Initial Release"
5. Add detailed release notes
6. Publish release

### 3. Share on Social Media

Announce on:
- Twitter/X with #Laravel hashtag
- Laravel News submission
- Reddit r/laravel
- Dev.to article
- Laravel.io forum

### 4. Add Badges to README

Add these badges at the top of README.md:

```markdown
[![Latest Version](https://img.shields.io/packagist/v/stuntrocket/rover.svg?style=flat-square)](https://packagist.org/packages/stuntrocket/rover)
[![Total Downloads](https://img.shields.io/packagist/dt/stuntrocket/rover.svg?style=flat-square)](https://packagist.org/packages/stuntrocket/rover)
[![License](https://img.shields.io/packagist/l/stuntrocket/rover.svg?style=flat-square)](https://packagist.org/packages/stuntrocket/rover)
```

## Maintenance

### Monitoring

- Check Packagist stats: https://packagist.org/packages/stuntrocket/rover/stats
- Monitor GitHub issues
- Respond to package questions

### Updating Dependencies

Periodically update `consolidation/robo`:

```bash
composer update consolidation/robo
# Test thoroughly
git commit -m "Update dependencies"
git tag -a v1.0.2 -m "Update dependencies"
git push origin main v1.0.2
```

### Support Policy

Document your support policy in README:
- Which versions are supported
- Security update policy
- Laravel version compatibility
- PHP version requirements

## Troubleshooting

### "Package not found"
- Verify GitHub repo is public
- Check composer.json name matches Packagist
- Wait a few minutes after submission

### "Invalid composer.json"
- Validate: `composer validate`
- Check required fields: name, description, license, autoload

### "Tag not showing on Packagist"
- Verify tag is pushed: `git ls-remote --tags origin`
- Trigger manual update on Packagist
- Check webhook is configured

### "Installation fails"
- Test in fresh project: `composer require stuntrocket/rover --dev`
- Check PHP version requirements
- Verify all dependencies are available

## Quick Reference

```bash
# Create release
git tag -a v1.0.0 -m "Release message"
git push origin v1.0.0

# Delete tag (if mistake)
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0

# List tags
git tag -l

# View tag details
git show v1.0.0

# Manual Packagist update (if webhook fails)
# Visit: https://packagist.org/packages/stuntrocket/rover
# Click "Update"
```

## Resources

- Packagist: https://packagist.org
- Composer documentation: https://getcomposer.org/doc/
- Semantic Versioning: https://semver.org
- GitHub Releases: https://docs.github.com/en/repositories/releasing-projects-on-github

---

**Next Steps:**
1. Push to GitHub
2. Create v1.0.0 tag
3. Submit to Packagist
4. Set up webhook
5. Share with the Laravel community! 🚀
