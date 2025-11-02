DATETIME of last agent review: 02/11/2025 10:22 UTC

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
│   └── models/
├── integration/        # Integration tests
│   └── controllers/
│       ├── ApiNightlyTest.php
│       ├── EventsTest.php
│       ├── SearchNodesTest.php
│       └── ToolsControllerTest.php
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
