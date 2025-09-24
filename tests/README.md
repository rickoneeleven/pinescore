DATETIME of last agent review: 24/09/2025 11:02 BST

# Test Suite

## Running Tests

To run all tests:
```bash
cd tests
php run-tests.php
```

## Test Structure

```
tests/
├── unit/               # Unit tests (isolated component testing)
│   └── models/         # Model tests
├── integration/        # Integration tests (multiple components)
│   └── controllers/    # Controller tests
├── bootstrap.php       # Test environment setup
└── run-tests.php       # Auto-discovery test runner
```

## Writing Tests

Tests extend the `TestCase` class and use these assertion methods:
- `assertTrue($condition, $message)`
- `assertFalse($condition, $message)`
- `assertEquals($expected, $actual, $message)`
- `assertNotNull($value, $message)`
- `assertNull($value, $message)`
- `assertInstanceOf($expected, $actual, $message)`

Test methods must start with `test` to be auto-discovered.

## Example Test

```php
class ExampleTest extends TestCase
{
    public function setUp()
    {
        // Setup before each test
    }

    public function testSomething()
    {
        $result = 2 + 2;
        $this->assertEquals(4, $result);
    }
}
```