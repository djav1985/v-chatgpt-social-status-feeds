<?php

namespace Tests;

use App\Helpers\ValidationHelper;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    /**
     * Test sanitizeString with various character sets
     */
    public function testSanitizeString(): void
    {
        // alphanumeric
        $this->assertEquals('abc123', ValidationHelper::sanitizeString('abc-123!@#', 'alphanumeric'));
        $this->assertEquals('hello', ValidationHelper::sanitizeString(' hello ', 'alphanumeric'));

        // alphanumeric-dash
        $this->assertEquals('test-123', ValidationHelper::sanitizeString('test-123!@#', 'alphanumeric-dash'));

        // alphanumeric-space
        $this->assertEquals('hello world 123', ValidationHelper::sanitizeString('hello world 123!@#', 'alphanumeric-space'));

        // text (with punctuation)
        $expected = 'Hello, world! It\'s "great".';
        $input = 'Hello, world! It\'s "great".';
        $this->assertEquals($expected, ValidationHelper::sanitizeString($input, 'text'));
        $this->assertEquals('Hello world', ValidationHelper::sanitizeString('Hello world@#$%', 'text'));

        // default (alphanumeric)
        $this->assertEquals('test123', ValidationHelper::sanitizeString('test-123', 'invalid-type'));
    }

    /**
     * Test sanitizeArray with callable
     */
    public function testSanitizeArray(): void
    {
        $input = ['hello!', 'world@', 'test#123'];
        $result = ValidationHelper::sanitizeArray($input, function ($item) {
            return ValidationHelper::sanitizeString($item, 'alphanumeric');
        });

        $this->assertEquals(['hello', 'world', 'test123'], $result);

        // Test with trim
        $input2 = [' test ', ' hello ', ' world '];
        $result2 = ValidationHelper::sanitizeArray($input2, 'trim');
        $this->assertEquals(['test', 'hello', 'world'], $result2);
    }

    /**
     * Test validateInteger with boundaries
     */
    public function testValidateInteger(): void
    {
        // Valid integers
        $this->assertEquals(5, ValidationHelper::validateInteger(5));
        $this->assertEquals(10, ValidationHelper::validateInteger('10'));
        $this->assertEquals(0, ValidationHelper::validateInteger(0));
        $this->assertEquals(-5, ValidationHelper::validateInteger(-5));
        $this->assertEquals(-10, ValidationHelper::validateInteger('-10'));

        // With min/max boundaries
        $this->assertEquals(5, ValidationHelper::validateInteger(5, 0, 10));
        $this->assertEquals(0, ValidationHelper::validateInteger(0, 0, 10));
        $this->assertEquals(10, ValidationHelper::validateInteger(10, 0, 10));

        // Out of bounds
        $this->assertNull(ValidationHelper::validateInteger(-1, 0, 10));
        $this->assertNull(ValidationHelper::validateInteger(11, 0, 10));

        // Invalid inputs
        $this->assertNull(ValidationHelper::validateInteger('abc'));
        $this->assertNull(ValidationHelper::validateInteger(null));
        $this->assertNull(ValidationHelper::validateInteger([]));

        // Reject floats and scientific notation (stricter validation)
        $this->assertNull(ValidationHelper::validateInteger(3.14));
        $this->assertNull(ValidationHelper::validateInteger('3.14'));
        $this->assertNull(ValidationHelper::validateInteger('1e2'));
    }

    /**
     * Test validateDaysArray
     */
    public function testValidateDaysArray(): void
    {
        // Valid 'everyday'
        $this->assertEmpty(ValidationHelper::validateDaysArray('everyday'));

        // Valid array of days
        $this->assertEmpty(ValidationHelper::validateDaysArray(['monday', 'tuesday', 'wednesday']));
        $this->assertEmpty(ValidationHelper::validateDaysArray(['friday']));
        $this->assertEmpty(ValidationHelper::validateDaysArray(['Monday', 'Tuesday'])); // Case insensitive

        // All days
        $allDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $this->assertEmpty(ValidationHelper::validateDaysArray($allDays));

        // Invalid: not array or 'everyday'
        $errors = ValidationHelper::validateDaysArray('invalid');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Days must be either "everyday" or an array', $errors[0]);

        // Invalid: empty array
        $errors = ValidationHelper::validateDaysArray([]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('At least one day must be selected', $errors[0]);

        // Invalid: invalid day name
        $errors = ValidationHelper::validateDaysArray(['monday', 'invalidday']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid day name', $errors[0]);
    }

    /**
     * Test validateCronArray
     */
    public function testValidateCronArray(): void
    {
        // Valid cron arrays
        $this->assertEmpty(ValidationHelper::validateCronArray([0, 6, 12, 18]));
        $this->assertEmpty(ValidationHelper::validateCronArray(['0', '12', '23']));
        $this->assertEmpty(ValidationHelper::validateCronArray([0]));
        $this->assertEmpty(ValidationHelper::validateCronArray(['null', 0, 'null', 12]));
        $this->assertEmpty(ValidationHelper::validateCronArray([null, 0, null, 12]));

        // Invalid: not an array
        $errors = ValidationHelper::validateCronArray('not-array');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Cron schedule must be an array', $errors[0]);

        // Invalid: empty array
        $errors = ValidationHelper::validateCronArray([]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('At least one hour must be specified', $errors[0]);

        // Invalid: hour out of range
        $errors = ValidationHelper::validateCronArray([0, 24, 12]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('between 0 and 23', $errors[0]);

        $errors = ValidationHelper::validateCronArray([0, -1, 12]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('between 0 and 23', $errors[0]);

        // Invalid: non-numeric
        $errors = ValidationHelper::validateCronArray([0, 'abc', 12]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Hours must be numeric', $errors[0]);
    }

    /**
     * Test escapeOutput
     */
    public function testEscapeOutput(): void
    {
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        $input = '<script>alert("xss")</script>';
        $this->assertEquals($expected, ValidationHelper::escapeOutput($input));
        $this->assertEquals('It&#039;s &quot;great&quot;', ValidationHelper::escapeOutput('It\'s "great"'));
        $this->assertEquals('hello world', ValidationHelper::escapeOutput('hello world'));
        $this->assertEquals('&amp;&lt;&gt;&quot;&#039;', ValidationHelper::escapeOutput('&<>"\''));

        // Test with different flags
        $this->assertEquals('&amp;&lt;&gt;"\'', ValidationHelper::escapeOutput('&<>"\'', ENT_NOQUOTES));
    }

    /**
     * Test validateUrl
     */
    public function testValidateUrl(): void
    {
        // Valid URLs
        $this->assertEmpty(ValidationHelper::validateUrl('https://example.com'));
        $this->assertEmpty(ValidationHelper::validateUrl('http://example.com'));
        $this->assertEmpty(ValidationHelper::validateUrl('https://example.com/path/to/page'));
        $this->assertEmpty(ValidationHelper::validateUrl('https://example.com?query=1'));

        // Valid with HTTPS requirement
        $this->assertEmpty(ValidationHelper::validateUrl('https://example.com', true));

        // Invalid: empty URL
        $errors = ValidationHelper::validateUrl('');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('URL cannot be empty', $errors[0]);

        // Invalid: malformed URL
        $errors = ValidationHelper::validateUrl('not-a-url');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid URL format', $errors[0]);

        // Invalid: HTTP when HTTPS required
        $errors = ValidationHelper::validateUrl('http://example.com', true);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('URL must use HTTPS protocol', $errors[0]);
    }

    /**
     * Test existing validateUser method (ensure not broken)
     */
    public function testValidateUser(): void
    {
        // Valid user
        $this->assertEmpty(ValidationHelper::validateUser([
            'username' => 'testuser',
            'password' => 'Pass123!',
            'email' => 'test@example.com'
        ]));

        // Invalid username
        $errors = ValidationHelper::validateUser([
            'username' => 'ab',
            'password' => 'Pass123!',
            'email' => 'test@example.com'
        ]);
        $this->assertNotEmpty($errors);
    }

    /**
     * Test existing validateAccount method (ensure not broken)
     */
    public function testValidateAccount(): void
    {
        // Valid account
        $this->assertEmpty(ValidationHelper::validateAccount([
            'accountName' => 'test-account',
            'link' => 'https://example.com'
        ]));

        // Invalid account name
        $errors = ValidationHelper::validateAccount([
            'accountName' => 'ab',
            'link' => 'https://example.com'
        ]);
        $this->assertNotEmpty($errors);
    }

    /**
     * Test existing validateLogin method (ensure not broken)
     */
    public function testValidateLogin(): void
    {
        // Valid login
        $this->assertEmpty(ValidationHelper::validateLogin([
            'username' => 'testuser',
            'password' => 'password123'
        ]));

        // Invalid: missing username
        $errors = ValidationHelper::validateLogin([
            'username' => '',
            'password' => 'password123'
        ]);
        $this->assertNotEmpty($errors);
    }
}
