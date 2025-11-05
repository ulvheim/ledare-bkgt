# BKGT Plugin Test Suite

This directory contains comprehensive tests for the BKGT Ledare WordPress plugin system.

## Test Structure

```
tests/
├── bootstrap.php          # PHPUnit bootstrap and test environment setup
├── TestCase.php           # Base test case class with common utilities
├── TestHelper.php         # Test data generation and helper functions
├── phpunit.xml           # PHPUnit configuration
├── unit/                 # Unit tests for individual components
│   ├── BKGT_Data_Scraping_Test.php
│   ├── BKGT_Team_Player_Test.php
│   └── BKGT_Inventory_Test.php
├── integration/          # Integration tests for component interactions
│   └── BKGT_Shortcode_Integration_Test.php
├── functional/           # Functional tests for end-to-end scenarios
│   └── BKGT_Page_Template_Functional_Test.php
└── database/             # Database-specific tests
    └── BKGT_Database_Test.php
```

## Running Tests

### Prerequisites

1. **PHPUnit**: Install PHPUnit globally or as a project dependency
   ```bash
   composer require --dev phpunit/phpunit
   ```

2. **WordPress Test Environment**: For full integration tests, set up WordPress testing environment
   ```bash
   # Install WordPress test suite
   bash wp-tests-config.sh
   ```

### Basic Test Execution

```bash
# Run all tests
phpunit

# Run specific test suite
phpunit --testsuite "BKGT Plugin Tests"

# Run specific test class
phpunit tests/unit/BKGT_Data_Scraping_Test.php

# Run with coverage report
phpunit --coverage-html coverage/
```

### Test Categories

#### Unit Tests (`tests/unit/`)
- Test individual plugin classes and methods
- Mock external dependencies
- Focus on isolated functionality

#### Integration Tests (`tests/integration/`)
- Test shortcode interactions
- Verify component communication
- Test data flow between plugins

#### Functional Tests (`tests/functional/`)
- Test complete page rendering
- User role and permission testing
- End-to-end user scenarios

#### Database Tests (`tests/database/`)
- Test table schemas and constraints
- Data integrity validation
- Performance and query optimization

## Test Data

Tests use generated test data through `TestHelper` class:

- **Players**: Sample football players with positions, teams, stats
- **Events**: Match schedules and results
- **Inventory**: Equipment items with assignments
- **Documents**: File management test data

## Test Utilities

### BKGT_TestCase
Base test class providing:
- Database setup/cleanup
- Shortcode testing helpers
- User mocking utilities
- Table existence validation

### BKGT_TestHelper
Utility functions for:
- Generating realistic test data
- Validating data structures
- Cleaning up test files
- Common assertions

## Continuous Integration

Tests are designed to run in CI environments:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: |
    composer install
    phpunit --testsuite "BKGT Plugin Tests" --coverage-clover coverage.xml
```

## Test Coverage

Current test coverage includes:

- ✅ Plugin initialization and activation
- ✅ Database table creation and schema validation
- ✅ Shortcode registration and output
- ✅ Data insertion, update, and deletion operations
- ✅ User role and permission handling
- ✅ Page template rendering
- ✅ Performance and load testing
- ✅ Data integrity and constraints

## Writing New Tests

1. **Extend BKGT_TestCase** for new test classes
2. **Use TestHelper** for generating test data
3. **Follow naming conventions**: `ClassName_Test.php`
4. **Group related tests** in appropriate directories
5. **Include setup and cleanup** methods as needed

### Example Test Structure

```php
class My_New_Feature_Test extends BKGT_TestCase {

    protected function setupTestData() {
        // Set up test data
    }

    protected function cleanupTestData() {
        // Clean up after tests
    }

    public function test_feature_functionality() {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

## Troubleshooting

### Common Issues

1. **Database Connection**: Ensure test database is configured in `phpunit.xml`
2. **Missing Dependencies**: Install required PHP extensions and WordPress
3. **Permission Issues**: Ensure proper file permissions for test directories
4. **Memory Limits**: Increase PHP memory limit for large test suites

### Debug Mode

Enable debug output in `bootstrap.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Contributing

When adding new features:

1. Write tests first (TDD approach)
2. Ensure all tests pass
3. Maintain or improve code coverage
4. Update this README for new test categories

## Performance Benchmarks

- Unit tests: < 100ms per test
- Integration tests: < 500ms per test
- Database tests: < 2s per test
- Full test suite: < 30s

Regular performance monitoring ensures tests remain fast and reliable.