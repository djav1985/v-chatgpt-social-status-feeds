<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Helpers;

use Respect\Validation\Validator as v;

class Validation
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

        if ($password !== '') {
            if (!v::regex('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[\W_]).{8,16}$/')->validate($password)) {
                $errors[] = 'Password must be 8-16 characters long, including at least one letter, one number, and one symbol.';
            }
        }

        if (!v::email()->validate($email)) {
            $errors[] = 'Please provide a valid email address.';
        }

        return $errors;
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

        if ($link !== '' && !v::url()->startsWith('https://')->validate($link)) {
            $errors[] = 'Link must be a valid URL starting with https://.';
        }

        // Optional: cron array validation
        if (isset($data['cronArr']) && is_array($data['cronArr'])) {
            foreach ($data['cronArr'] as $hour) {
                if ($hour === 'null') {
                    continue;
                }
                // Accept numeric string or int
                if (!v::digit()->validate((string) $hour)) {
                    $errors[] = 'Invalid cron hour(s) supplied. Hours must be numeric between 0 and 23.';
                    break;
                }
                $intHour = (int) $hour;
                if ($intHour < 0 || $intHour > 23) {
                    $errors[] = 'Invalid cron hour(s) supplied. Hours must be between 0 and 23.';
                    break;
                }
            }
        }

        return $errors;
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
}
