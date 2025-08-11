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

use App\Models\Blacklist;
use App\Core\ErrorManager;

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
     * Start the session with secure cookie parameters.
     *
     * @return void
     */
    public function start(): void
    {
        $secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secureFlag,
            'samesite' => 'Lax',
        ]);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Destroy the current session and its data.
     *
     * @return void
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
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
     * Enforce that the current request comes from an authenticated user.
     * Redirects to the login page or exits on failure.
     *
     * @return void
     */
    public function requireAuth(): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if ($ip && Blacklist::isBlacklisted($ip)) {
            http_response_code(403);
            ErrorManager::getInstance()->log("Blacklisted IP attempted access: $ip", 'error');
            exit();
        }

        if (!$this->isValid()) {
            header('Location: /login');
            exit();
        }
    }
}
