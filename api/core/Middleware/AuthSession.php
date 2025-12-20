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
        // Use PHP 5.6 compatible environment variable checks
        $sessionName = isset($_ENV['AUTH_SESSION_NAME']) ? $_ENV['AUTH_SESSION_NAME'] : 'PHP_SESSION';
        session_name($sessionName);

        if (session_status() === PHP_SESSION_NONE) {
            // PHP 5.6 does not support 'samesite' in session_set_cookie_params
            $lifetime = isset($_ENV['AUTH_SESSION_LIFETIME']) ? (int)$_ENV['AUTH_SESSION_LIFETIME'] : 0;
            $path = '/';
            $domain = '';
            $secure = isset($_ENV['AUTH_SESSION_SECURE']) ? ($_ENV['AUTH_SESSION_SECURE'] === '1' || $_ENV['AUTH_SESSION_SECURE'] === 'true' || $_ENV['AUTH_SESSION_SECURE'] === true) : false;
            $httponly = isset($_ENV['AUTH_SESSION_HTTPONLY']) ? ($_ENV['AUTH_SESSION_HTTPONLY'] === '1' || $_ENV['AUTH_SESSION_HTTPONLY'] === 'true' || $_ENV['AUTH_SESSION_HTTPONLY'] === true) : true;

            // Order: lifetime, path, domain, secure, httponly for PHP 5.6
            session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);

            // Note: samesite cannot be set directly in PHP 5.6,
            // If required, must use header() to set Set-Cookie manually.

            session_start();
        }

        // Check if session is set and user is authenticated
        if (!isset($_SESSION[$this->sessionKey])) {
            // Use array() for PHP 5.6
            return $controller->response(array(
                "status" => 401,
                "error"  => "Unauthorized - please login"
            ), 401);
        }

        // Attach user session data to controller if needed
        $controller->setUserData($_SESSION[$this->sessionKey]);

        // Call next handler (controller action)
        return $next();
    }
}
