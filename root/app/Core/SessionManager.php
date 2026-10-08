<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

class SessionManager
{
    /**
     * Singleton instance of the SessionManager.
     */
    private static ?SessionManager $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Retrieve the singleton instance.
     *
     * @return SessionManager
     */
    public static function getInstance(): SessionManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Destroy the current session, its data and the session cookie.
     *
     * @return void
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]);
            }
            $_SESSION = [];
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
     * Validate the current session and update timeout.
     *
     * @return bool True if the session is valid and user is logged in
     */
    public function isValid(): bool
    {
        $timeoutLimit = defined('SESSION_TIMEOUT_LIMIT') ? SESSION_TIMEOUT_LIMIT : 1800;
        $timeout = $this->get('timeout');
        $timeoutExceeded = is_int($timeout) && (time() - $timeout > $timeoutLimit);

        $userAgent = $this->get('user_agent');
        $userAgentChanged = is_string($userAgent) && $userAgent !== ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($timeoutExceeded || $userAgentChanged) {
            $this->destroy();
            return false;
        }

        $this->set('timeout', time());

        return $this->get('logged_in') === true;
    }

    /**
     * Create the CSRF token when the session has none.
     */
    public function ensureCsrfToken(): void
    {
        if (!$this->get('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    /**
     * Whether the current request comes from an authenticated user.
     *
     * @return bool True if authenticated, false if not.
     */
    public function requireAuth(): bool
    {
        return $this->isValid();
    }

    /**
     * Start the session on demand with strict mode and secure cookie parameters.
     */
    private function initialize(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secureFlag,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
