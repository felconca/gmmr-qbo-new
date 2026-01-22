<?php

namespace Core\Middleware;

class AuthSession
{
    private $sessionKey;

    // No type hinting for PHP 5.6 compatibility
    public function __construct($sessionKey = "user")
    {
        $this->sessionKey = $sessionKey;
    }

    // $controller and $next are not type-hinted for PHP 5.6 compatibility
    public function handle($controller, $next)
    {
        // Set session name BEFORE session_start
        if (session_status() === PHP_SESSION_NONE) {
            $sessionName = isset($_ENV['AUTH_SESSION_NAME']) ? $_ENV['AUTH_SESSION_NAME'] : null;
            if (!$sessionName) {
                $sessionName = 'PHP_SESSION';
            }
            session_name($sessionName);

            $lifetime = isset($_ENV['AUTH_SESSION_LIFETIME']) ? (int)$_ENV['AUTH_SESSION_LIFETIME'] : 0;

            session_set_cookie_params(
                $lifetime,
                '/',
                '',
                isset($_ENV['AUTH_SESSION_SECURE']) && $_ENV['AUTH_SESSION_SECURE'] === 'true',
                true
            );

            session_start();
        }

        // Auth check
        if (!isset($_SESSION[$this->sessionKey])) {
            session_write_close(); // 🔥 RELEASE LOCK
            return $controller->response(array(
                "status" => 401,
                "error"  => "Unauthorized - please login"
            ), 401);
        }

        $controller->setUserData($_SESSION[$this->sessionKey]);

        session_write_close(); // 🔥 RELEASE LOCK BEFORE NEXT HANDLER
        return $next();
    }
}
