<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SessionManager.php
 * Description: Centralized session management
 */

namespace App\Core;

/**
 * Singleton manager for PHP session and authentication operations.
 */
class SessionManager
{
    private static ?self $instance = null;

    /**
     * Prevent direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton session manager instance.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key to retrieve.
     * @param mixed  $default Default value if key does not exist.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->initialize();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key   Session key to set.
     * @param mixed  $value Value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->initialize();
        $_SESSION[$key] = $value;
    }

    /**
     * Destroy the current session and its data.
     *
     * @return void
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]);
            }
            session_unset();
            session_destroy();
        }
    }

    /**
     * Regenerate the session ID to prevent fixation.
     *
     * @return void
     */
    public function regenerate(): void
    {
        $this->initialize();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Create the CSRF token when the session has none.
     */
    public function ensureCsrfToken(): void
    {
        if (!$this->get('csrf_token')) {
            $this->set('csrf_token', bin2hex(\App\Helpers\EncryptionHelper::bytes(32)));
        }
    }

    /**
     * Check session timeout, user agent and login state; destroys the session when expired.
     */
    public function isValid(?string $currentUserAgent = null): bool
    {
        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = $this->get('timeout');
        $timeoutExceeded = is_int($timeout) && (time() - $timeout > $timeoutLimit);

        $userAgent = $this->get('user_agent');
        $currentUserAgent ??= $_SERVER['HTTP_USER_AGENT'] ?? '';
        $userAgentChanged = is_string($userAgent) && $userAgent !== $currentUserAgent;

        if ($timeoutExceeded || $userAgentChanged) {
            $this->destroy();
            return false;
        }

        $this->set('timeout', time());

        return $this->get('logged_in') === true;
    }

    /**
     * Whether the current request comes from an authenticated user.
     */
    public function requireAuth(?string $currentUserAgent = null): bool
    {
        return $this->isValid($currentUserAgent);
    }

    /**
     * Initialize PHP session with secure settings.
     *
     * @return void
     */
    private function initialize(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_secure', $secureFlag ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');

        session_set_cookie_params([
            'path' => '/',
            'httponly' => true,
            'secure' => $secureFlag,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
