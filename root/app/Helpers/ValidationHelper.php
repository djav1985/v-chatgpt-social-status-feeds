<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Helpers;

use Respect\Validation\Validator as v;

class ValidationHelper
{
    /**
     * Validate user creation/update input.
     * Returns an array of error messages (empty = valid).
     *
     * @param array<string,mixed> $data
     * @return string[]
     */
    public static function validateUser(array $data): array
    {
        $errors = [];

        $username = trim((string) ($data['username'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if (!v::alnum()->noWhitespace()->lowercase()->length(5, 16)->validate($username)) {
            $errors[] = 'Username must be 5-16 characters long, lowercase letters and numbers only.';
        }

        if ($password !== '' && !self::isValidPassword($password)) {
            $errors[] = 'Password must be 8-16 characters long, including at least one letter, one number, and one symbol.';
        }

        if (!v::email()->validate($email)) {
            $errors[] = 'Please provide a valid email address.';
        }

        return $errors;
    }
    
    /**
     * Check if password meets requirements.
     *
     * @param string $password Password to validate
     * @return bool True if valid
     */
    private static function isValidPassword(string $password): bool
    {
        return v::regex('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/')->validate($password);
    }

    /**
     * Validate account creation/update input.
     * Returns an array of error messages (empty = valid).
     *
     * @param array<string,mixed> $data
     * @return string[]
     */
    public static function validateAccount(array $data): array
    {
        $errors = [];

        $accountName = trim((string) ($data['accountName'] ?? ''));
        $link = trim((string) ($data['link'] ?? ''));

        if (!v::alnum('-')->noWhitespace()->lowercase()->length(8, 18)->validate($accountName)) {
            $errors[] = 'Account name must be 8-18 characters long, alphanumeric and hyphens only.';
        }

        if ($link !== '' && !self::isValidHttpsUrl($link)) {
            $errors[] = 'Link must be a valid URL starting with https://.';
        }

        // Optional: cron array validation
        if (isset($data['cronArr']) && is_array($data['cronArr'])) {
            $cronErrors = self::validateCronArray($data['cronArr']);
            $errors = array_merge($errors, $cronErrors);
        }

        return $errors;
    }
    
    /**
     * Check if URL is valid HTTPS URL.
     *
     * @param string $url URL to validate
     * @return bool True if valid
     */
    private static function isValidHttpsUrl(string $url): bool
    {
        return v::url()->startsWith('https://')->validate($url);
    }

    /**
     * Validate login input.
     *
     * @param array<string,mixed> $data
     * @return string[]
     */
    public static function validateLogin(array $data): array
    {
        $errors = [];
        $username = trim((string) ($data['username'] ?? ''));
        $password = trim((string) ($data['password'] ?? ''));

        if ($username === '') {
            $errors[] = 'Username is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        // Optionally enforce username format for login attempts (same as creation rules)
        if (!v::alnum()->noWhitespace()->lowercase()->length(5, 16)->validate($username)) {
            $errors[] = 'Invalid username format.';
        }

        return $errors;
    }

    /**
     * Sanitize string input by allowed character set.
     *
     * @param string $input Input string to sanitize
     * @param string $allowedChars Character set: 'alphanumeric', 'alphanumeric-dash', 'alphanumeric-space', 'text'
     * @return string Sanitized string
     */
    public static function sanitizeString(string $input, string $allowedChars = 'alphanumeric'): string
    {
        $input = trim($input);

        switch ($allowedChars) {
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);
            case 'alphanumeric-dash':
                return preg_replace('/[^a-zA-Z0-9\-]/', '', $input);
            case 'alphanumeric-space':
                return preg_replace('/[^a-zA-Z0-9 ]/', '', $input);
            case 'text':
                // Allow letters, numbers, spaces, and common punctuation
                return preg_replace('/[^a-zA-Z0-9 .,!?\'\"-]/', '', $input);
            default:
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);
        }
    }

    /**
     * Sanitize array elements using a callable function.
     *
     * @param array<mixed> $array Array to sanitize
     * @param callable $sanitizer Function to apply to each element
     * @return array<mixed> Sanitized array
     */
    public static function sanitizeArray(array $array, callable $sanitizer): array
    {
        return array_map($sanitizer, $array);
    }

    /**
     * Validate integer input within optional min/max bounds.
     * Accepts integer values or string representations of integers only.
     *
     * @param mixed $value Value to validate
     * @param int|null $min Minimum value (inclusive)
     * @param int|null $max Maximum value (inclusive)
     * @return int|null Validated integer or null on failure
     */
    public static function validateInteger($value, ?int $min = null, ?int $max = null): ?int
    {
        $intValue = self::convertToInteger($value);
        
        if ($intValue === null) {
            return null;
        }
        
        return self::validateIntegerBounds($intValue, $min, $max);
    }
    
    /**
     * Convert value to integer if valid.
     *
     * @param mixed $value Value to convert
     * @return int|null Integer value or null on failure
     */
    private static function convertToInteger($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        
        return null;
    }
    
    /**
     * Validate integer is within bounds.
     *
     * @param int $value Integer to validate
     * @param int|null $min Minimum value (inclusive)
     * @param int|null $max Maximum value (inclusive)
     * @return int|null Integer or null if out of bounds
     */
    private static function validateIntegerBounds(int $value, ?int $min, ?int $max): ?int
    {
        if ($min !== null && $value < $min) {
            return null;
        }
        
        if ($max !== null && $value > $max) {
            return null;
        }
        
        return $value;
    }

    /**
     * Validate days array for account scheduling.
     * Valid values: 'everyday' or array of day names.
     *
     * @param mixed $days Days value to validate
     * @return string[] Array of validation errors (empty = valid)
     */
    public static function validateDaysArray($days): array
    {
        $errors = [];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        if ($days === 'everyday') {
            return $errors;
        }

        if (!is_array($days)) {
            $errors[] = 'Days must be either "everyday" or an array of day names.';
            return $errors;
        }

        if (empty($days)) {
            $errors[] = 'At least one day must be selected.';
            return $errors;
        }

        foreach ($days as $day) {
            if (!in_array(strtolower((string) $day), $validDays, true)) {
                $errors[] = sprintf('Invalid day name: %s. Valid days are: %s', $day, implode(', ', $validDays));
                break;
            }
        }

        return $errors;
    }

    /**
     * Validate cron array of hour values.
     * Valid values: array of hour integers (0-23) or 'null' strings.
     *
     * @param mixed $cron Cron array to validate
     * @return string[] Array of validation errors (empty = valid)
     */
    public static function validateCronArray($cron): array
    {
        if (!is_array($cron)) {
            return ['Cron schedule must be an array.'];
        }

        if (empty($cron)) {
            return ['At least one hour must be specified in the cron schedule.'];
        }

        return self::validateCronHours($cron);
    }
    
    /**
     * Validate individual cron hour values.
     *
     * @param array $cron Array of hour values
     * @return string[] Array of validation errors (empty = valid)
     */
    private static function validateCronHours(array $cron): array
    {
        foreach ($cron as $hour) {
            if ($hour === 'null' || $hour === null) {
                continue;
            }

            if (!self::isValidCronHour($hour)) {
                return ['Invalid cron hour(s) supplied. Hours must be numeric between 0 and 23.'];
            }
        }

        return [];
    }
    
    /**
     * Check if a cron hour value is valid.
     *
     * @param mixed $hour Hour value to check
     * @return bool True if valid, false otherwise
     */
    private static function isValidCronHour($hour): bool
    {
        if (!v::digit()->validate((string) $hour)) {
            return false;
        }

        $intHour = (int) $hour;
        return $intHour >= 0 && $intHour <= 23;
    }

    /**
     * Escape output for safe HTML rendering.
     * Wrapper for htmlspecialchars with ENT_QUOTES by default.
     *
     * @param string $value Value to escape
     * @param int $flags Optional flags for htmlspecialchars
     * @return string Escaped string
     */
    public static function escapeOutput(string $value, int $flags = ENT_QUOTES): string
    {
        return htmlspecialchars($value, $flags, 'UTF-8');
    }

    /**
     * Validate URL with optional HTTPS requirement.
     *
     * @param string $url URL to validate
     * @param bool $requireHttps Whether to require HTTPS protocol
     * @return string[] Array of validation errors (empty = valid)
     */
    public static function validateUrl(string $url, bool $requireHttps = false): array
    {
        $errors = [];
        $url = trim($url);

        if ($url === '') {
            $errors[] = 'URL cannot be empty.';
            return $errors;
        }

        if (!v::url()->validate($url)) {
            $errors[] = 'Invalid URL format.';
            return $errors;
        }

        if ($requireHttps && !v::startsWith('https://')->validate($url)) {
            $errors[] = 'URL must use HTTPS protocol.';
        }

        return $errors;
    }
}
