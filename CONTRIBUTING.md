# 🤝 Contributing to Modulite

Thank you for considering contributing to Modulite! We welcome contributions from the community and are grateful for your support.

## 📋 Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Guidelines](#contributing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Issue Guidelines](#issue-guidelines)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Documentation](#documentation)
- [Release Process](#release-process)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

Examples of behavior that contributes to creating a positive environment include:

- Using welcoming and inclusive language
- Being respectful of differing viewpoints and experiences
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

### Enforcement

Instances of abusive, harassing, or otherwise unacceptable behavior may be reported by contacting the project team at conduct@panicdevs.agency.

## Getting Started

### Prerequisites

Before you begin, ensure you have:

- **PHP 8.2+** with required extensions
- **Composer** for dependency management
- **Git** for version control
- **Node.js & NPM** (for documentation development)
- A **Laravel application** for testing

### Fork and Clone

1. **Fork the repository** `panicdevs/modulite` on GitHub to your own account
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/modulite.git
   cd modulite
   ```

**Example:** If John wants to contribute:
- John forks `panicdevs/modulite` → creates `john/modulite`
- John clones: `git clone https://github.com/john/modulite.git`
- John works on `john/modulite` and sends PR to `panicdevs/modulite`

That's it! No additional remotes needed.

## Development Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install development tools
composer install --dev
```

### 2. Set Up Test Environment

```bash
# Copy environment file
cp .env.example .env

# Set up test database
php artisan migrate --env=testing

# Run tests to verify setup
composer test
```

### 3. Configure IDE

**VS Code:**
```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": false,
    "phpcs.enable": true,
    "phpcs.standard": "PSR12"
}
```

**PhpStorm:**
- Enable PHP CS Fixer
- Set code style to PSR-12
- Configure PHPUnit test runner

### 4. Development Commands

```bash
# Run tests
composer test

# Run tests with coverage
composer test-coverage

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

## Contributing Guidelines

### Types of Contributions

We welcome several types of contributions:

1. **🐛 Bug Fixes** - Fix issues and improve reliability
2. **✨ New Features** - Add new functionality
3. **📚 Documentation** - Improve or add documentation
4. **⚡ Performance** - Optimize performance
5. **🧪 Tests** - Add or improve test coverage
6. **🔧 Tools** - Improve development tools and workflow

### Contribution Process

1. **Check existing issues** before starting work
2. **Create an issue** for new features or major changes
3. **Discuss the approach** with maintainers
4. **Fork the repository** to your GitHub account
5. **Create a branch** on your fork from `develop`
6. **Implement changes** following our guidelines
7. **Add tests** for new functionality
8. **Update documentation** as needed
9. **Push changes** to your fork
10. **Submit a pull request** from your fork to the original repository

### Branch Naming

Use descriptive branch names with prefixes:

```bash
# Features
feature/panel-discovery-optimization
feature/component-auto-registration

# Bug fixes
bugfix/cache-invalidation-issue
bugfix/memory-leak-scanner

# Documentation
docs/api-reference-update
docs/troubleshooting-guide

# Refactoring
refactor/service-architecture
refactor/command-structure
```

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# Format
type(scope): description

# Examples
feat(discovery): add component auto-registration
fix(cache): resolve memory leak in scanner
docs(api): update interface documentation
test(scanner): add edge case tests
refactor(services): improve service architecture
perf(cache): optimize file-based caching
```

**Types:**
- `feat` - New features
- `fix` - Bug fixes
- `docs` - Documentation changes
- `test` - Test additions/changes
- `refactor` - Code refactoring
- `perf` - Performance improvements
- `style` - Code style changes
- `chore` - Build/dependency updates

## Pull Request Process

### Before Submitting

1. **Run quality checks:**
   ```bash
   composer quality
   ```

2. **Update documentation** if needed

3. **Test thoroughly** on different environments

4. **Push to your fork:**
   ```bash
   git push origin your-feature-branch
   ```

### PR Template

When creating a pull request, use this template:

```markdown
## Description
Brief description of the changes made.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Manual testing completed

## Checklist
- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] New and existing unit tests pass locally with my changes

## Screenshots (if applicable)
Add screenshots to help explain your changes.

## Additional Notes
Any additional information or context about the changes.
```

### Review Process

1. **Automated checks** must pass
2. **Code review** by maintainers
3. **Manual testing** if needed
4. **Approval** from at least one maintainer
5. **Merge** by maintainers

## Issue Guidelines

### Before Creating an Issue

1. **Search existing issues** to avoid duplicates
2. **Check documentation** for solutions
3. **Try the latest version** to see if it's already fixed

### Issue Types

#### 🐛 Bug Reports

Use the bug report template:

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear and concise description of what you expected to happen.

**Environment:**
- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.2.1]
- Laravel Version: [e.g. 10.32.1]
- Filament Version: [e.g. 4.0.0]
- Modulite Version: [e.g. 1.0.0]

**Additional context**
Add any other context about the problem here.
```

#### ✨ Feature Requests

Use the feature request template:

```markdown
**Is your feature request related to a problem?**
A clear and concise description of what the problem is.

**Describe the solution you'd like**
A clear and concise description of what you want to happen.

**Describe alternatives you've considered**
A clear and concise description of any alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request here.
```

#### 📚 Documentation Issues

```markdown
**Documentation Issue**
- [ ] Missing documentation
- [ ] Incorrect documentation
- [ ] Unclear documentation
- [ ] Outdated documentation

**Page/Section**
Link to the documentation page or section.

**Description**
Clear description of the issue and suggested improvements.
```

## Development Workflow

### Setting Up for Development

1. **Create a test Laravel application:**
   ```bash
   composer create-project laravel/laravel modulite-test
   cd modulite-test
   ```

2. **Link your local Modulite:**
   ```json
   // composer.json
   {
       "repositories": [
           {
               "type": "path",
               "url": "../modulite"
           }
       ],
       "require": {
           "panicdevs/modulite": "*"
       }
   }
   ```

3. **Install and test:**
   ```bash
   composer install
   php artisan vendor:publish --tag=modulite-config
   ```

### Development Standards

#### Code Style

We follow **PSR-12** coding standards:

```php
<?php

declare(strict_types=1);

namespace PanicDevs\Modulite\Services;

use Illuminate\Support\Collection;

/**
 * Service class documentation.
 */
class ExampleService
{
    /**
     * Method documentation.
     */
    public function exampleMethod(string $parameter): Collection
    {
        // Implementation
        return collect();
    }
}
```

#### Architecture Principles

1. **SOLID Principles** - Follow SOLID design principles
2. **Interface Segregation** - Use focused interfaces
3. **Dependency Injection** - Proper DI container usage
4. **Single Responsibility** - One responsibility per class
5. **Open/Closed** - Open for extension, closed for modification

#### Performance Guidelines

1. **Lazy Loading** - Defer expensive operations
2. **Caching** - Cache expensive computations
3. **Memory Management** - Be mindful of memory usage
4. **File I/O** - Minimize file system operations
5. **Database Queries** - Optimize database access

### Error Handling

Follow these error handling patterns:

```php
// Use specific exceptions
throw new ScanException("Panel discovery failed: {$reason}");

// Proper exception handling
try {
    $result = $this->riskyOperation();
} catch (SpecificException $e) {
    $this->logError($e);
    
    if ($this->shouldFailSilently()) {
        return $defaultValue;
    }
    
    throw $e;
}

// Return types over exceptions when appropriate
public function findPanel(string $name): ?PanelProvider
{
    // Return null instead of throwing for "not found"
    return $this->panels[$name] ?? null;
}
```

## Testing

### Test Structure

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── CacheManagerTest.php
│   │   ├── PanelScannerTest.php
│   │   └── ComponentDiscoveryTest.php
│   └── Attributes/
│       └── FilamentPanelTest.php
├── Feature/
│   ├── PanelDiscoveryTest.php
│   ├── ComponentDiscoveryTest.php
│   └── CacheIntegrationTest.php
└── Integration/
    ├── ModuleIntegrationTest.php
    └── FilamentIntegrationTest.php
```

### Writing Tests

#### Unit Tests

```php
<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PanicDevs\Modulite\Services\UnifiedCacheManager;

class CacheManagerTest extends TestCase
{
    private UnifiedCacheManager $cache;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cache = new UnifiedCacheManager([
            'enabled' => true,
            'file' => '/tmp/test-cache.php',
        ]);
    }
    
    protected function tearDown(): void
    {
        $this->cache->flush();
        parent::tearDown();
    }
    
    public function test_can_store_and_retrieve_data(): void
    {
        $key = 'test-key';
        $value = ['test' => 'data'];
        
        $this->assertTrue($this->cache->put($key, $value));
        $this->assertEquals($value, $this->cache->get($key));
    }
    
    public function test_returns_default_for_missing_key(): void
    {
        $default = 'default-value';
        
        $this->assertEquals($default, $this->cache->get('missing-key', $default));
    }
}
```

#### Feature Tests

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use PanicDevs\Modulite\Contracts\PanelScannerInterface;

class PanelDiscoveryTest extends TestCase
{
    public function test_discovers_panels_with_attribute(): void
    {
        // Create test panel file
        $this->createTestPanel();
        
        $scanner = $this->app->make(PanelScannerInterface::class);
        $panels = $scanner->discoverPanels();
        
        $this->assertContains('Tests\\Fixtures\\TestPanelProvider', $panels);
    }
    
    private function createTestPanel(): void
    {
        // Implementation to create test panel
    }
}
```

### Test Commands

```bash
# Run all tests
composer test

# Run specific test suite
composer test -- --testsuite=Unit
composer test -- --testsuite=Feature

# Run with coverage
composer test-coverage

# Run specific test
composer test -- --filter=PanelDiscoveryTest

# Run tests with debugging
composer test -- --debug
```

### Test Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">./src</directory>
        </whitelist>
    </filter>
</phpunit>
```

## Documentation

### Documentation Standards

1. **Code Comments** - Document complex logic
2. **DocBlocks** - Complete PHPDoc for all public methods
3. **README Updates** - Update README for new features
4. **API Documentation** - Update API reference
5. **Examples** - Provide practical examples

### Documentation Types

#### Code Documentation

```php
/**
 * Discover all Filament Panel classes in configured locations.
 *
 * This method scans the configured directories for PHP files containing
 * classes marked with the #[FilamentPanel] attribute. It uses token
 * parsing for performance and caches results to avoid repeated scanning.
 *
 * @return array<string> Array of fully qualified class names with #[FilamentPanel] attribute
 * 
 * @throws ScanException When scanning fails critically
 * 
 * @example
 * ```php
 * $scanner = app(PanelScannerInterface::class);
 * $panels = $scanner->discoverPanels();
 * // ['Modules\Admin\Providers\Filament\Panels\AdminPanelProvider', ...]
 * ```
 */
public function discoverPanels(): array
{
    // Implementation
}
```

#### User Documentation

- Keep documentation up-to-date with code changes
- Use clear, concise language
- Provide practical examples
- Include troubleshooting information
- Test documentation examples

### Documentation Build

```bash
# Build documentation locally
npm install
npm run docs:build

# Serve documentation locally
npm run docs:serve

# Lint documentation
npm run docs:lint
```

## Release Process

### Version Management

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0) - Breaking changes
- **MINOR** (X.Y.0) - New features, backward compatible
- **PATCH** (X.Y.Z) - Bug fixes, backward compatible

### Release Checklist

#### Pre-Release

- [ ] All tests pass
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Version bumped in composer.json
- [ ] Tag created with proper version
- [ ] Release notes prepared

#### Release Process

1. **Create release branch:**
   ```bash
   git checkout develop
   git pull upstream develop
   git checkout -b release/v1.1.0
   ```

2. **Update version numbers:**
   ```json
   // composer.json
   {
       "version": "1.1.0"
   }
   ```

3. **Update CHANGELOG.md**

4. **Create pull request** to `main`

5. **After merge, create tag:**
   ```bash
   git tag -a v1.1.0 -m "Release version 1.1.0"
   git push upstream v1.1.0
   ```

6. **Create GitHub release** with release notes

#### Post-Release

- [ ] Announce on social media
- [ ] Update documentation site
- [ ] Notify community
- [ ] Monitor for issues

## Getting Help

### Development Support

- **Discord**: [Join our Discord](https://discord.gg/panicdevs)
- **GitHub Discussions**: [Community discussions](https://github.com/YOUR_USERNAME/modulite/discussions)
- **Email**: [dev@panicdevs.agency](mailto:dev@panicdevs.agency)

### Resources

- **Laravel Documentation**: [laravel.com/docs](https://laravel.com/docs)
- **Filament Documentation**: [filamentphp.com/docs](https://filamentphp.com/docs)
- **PSR-12 Standard**: [PHP-FIG PSR-12](https://www.php-fig.org/psr/psr-12/)
- **Conventional Commits**: [conventionalcommits.org](https://www.conventionalcommits.org/)

## Recognition

Contributors are recognized in:

- **CHANGELOG.md** - Feature contributors
- **README.md** - All contributors section
- **GitHub Contributors** - Automatic recognition
- **Release Notes** - Major contributors highlighted

## License

By contributing to Modulite, you agree that your contributions will be licensed under the [MIT License](LICENSE).

---

Thank you for contributing to Modulite! Your efforts help make this project better for everyone. 🙏

If you have any questions about contributing, feel free to reach out to us at [contribute@panicdevs.agency](mailto:contribute@panicdevs.agency).
