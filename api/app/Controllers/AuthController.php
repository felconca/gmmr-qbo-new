<?php

namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;

class AuthController extends Rest
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Manila');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');
        header("Access-Control-Allow-Credentials: true");

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        parent::__construct();

        $this->db = new Database();
    }

    public function index($request, $response, $params)
    {
        return $response(['message' => 'AuthController index'], 200);
    }
    /**
     * Handle login. Does NOT set a custom session timeout; uses PHP's default.
     *
     * By default, PHP session timeout is controlled by `session.gc_maxlifetime` in php.ini (usually 1440 seconds = 24 mins).
     * No explicit expiry/time handling is done here; session duration follows server setting unless managed elsewhere.
     */
    public function login($request, $response, $params)
    {
        try {
            $input = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $hash = md5($input['password']);

            $users = $this->db->ipadrbg()->SELECT("
                users.PxRID,
                users.UserType,
                users.userTypeRID,
                users.UserStatus,
                CONCAT (px_data.FirstName,' ',SUBSTRING(px_data.MiddleName, 1, 1), '. ', px_data.LastName) AS pxName,
                CONCAT (SUBSTRING(px_data.FirstName, 1, 1),'.', px_data.LastName) AS shortName,
                px_data.foto", "users")
                ->LEFTJOIN('px_data', 'users.PxRID = px_data.PxRID ')
                ->WHERE(['UserName' => $input['username'], 'PassWD' => $hash])
                ->first();

            if ($users) {
                $user = $users;

                ini_set('session.gc_maxlifetime', 1800);
                session_set_cookie_params(1800);
                session_name($_ENV['AUTH_SESSION_NAME']); // unique, branded

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                    $_SESSION['LAST_ACTIVITY'] = time();
                }

                // Clear any previous session data
                session_unset();
                session_destroy();
                session_start();
                session_regenerate_id(true);

                // No explicit session timeout management here;
                // uses PHP default lifetime settings.

                // Store user info in session
                $_SESSION["user"] = [
                    "id" => $user->PxRID,
                    "name" => $user->pxName,
                    "short_name" => $user->shortName,
                    "user_type_id" => $user->userTypeRID,
                    "user_status" => $user->UserStatus,
                    "profile" => $user->foto,
                    "user_type" => $user->UserType
                ];

                $success = [
                    "message" => "Login successful",
                    "status"  => 200,
                    "user"    => $_SESSION["user"]
                ];
                return $response($success, 200);
            } else {
                $error = array('status' => "error", "msg" => "Invalid username or password!");
                return $response($error, 401);
            }
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
    /**
     * API endpoint to verify if a user has a valid, active session (is authenticated).
     * 
     * HOW TO USE:
     * - Make a request (typically GET or POST) to the /auth/verify API endpoint.
     * - Your client (browser/app) must include the session cookie (sent automatically by browser or set manually).
     * - The endpoint will check if a session is active, refresh activity timeout, and return the user context if logged in.
     * 
     * Expected Results:
     * - 200 OK with user session info if authenticated.
     * - 401 Unauthorized if no active session or session expired.
     * 
     * Example (using fetch):
     *   fetch('/api/auth/verify', {credentials: 'include'})
     *     .then(r => r.json()).then(console.log)
     */
    public function verify($request, $response, $params)
    {
        session_name($_ENV['AUTH_SESSION_NAME']);

        if (!isset($_COOKIE[session_name()])) {
            return $response(["status" => 401, "error" => "No active session"], 401);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $timeout = 1800; // 30 minutes

        if (
            isset($_SESSION['LAST_ACTIVITY']) &&
            (time() - $_SESSION['LAST_ACTIVITY']) > $timeout
        ) {
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');

            return $response(["status" => 401, "error" => "Session expired"], 401);
        }

        // User must exist
        if (!isset($_SESSION['user'])) {
            return $response(["status" => 401, "error" => "Not logged in"], 401);
        }

        // Refresh activity timestamp (rolling session)
        $_SESSION['LAST_ACTIVITY'] = time();

        // **ADD THIS: Refresh the session cookie's expiration**
        $cookieParams = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            time() + $timeout, // Extend cookie lifetime
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );

        return $response([
            "status" => 200,
            "user" => $_SESSION['user'],
            "session" => $_SESSION['LAST_ACTIVITY']
        ], 200);
    }
    public function profile($request, $response, $params)
    {
        // Define upload directory
        $uploadDir = __DIR__ . '/../../uploads/';

        // Allowed extensions to check
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $filePath = null;
        $fileExt = null;

        // Try each extension
        foreach ($extensions as $ext) {
            $path = $uploadDir . $params['img'] . '.' . $ext;
            if (file_exists($path)) {
                $filePath = $path;
                $fileExt = $ext;
                break;
            }
        }

        // If not found, return placeholder or 404
        if (!$filePath) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Profile image not found']);
            exit;
        }

        // Correct MIME type
        $ext = strtolower($fileExt);
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            case 'webp':
                $mimeType = 'image/webp';
                break;
            default:
                $mimeType = 'application/octet-stream';
        }

        // Output image
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
    public function logout($request, $response, $params)
    {

        session_name($_ENV['AUTH_SESSION_NAME']); // unique, branded
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Clear all session variables
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();


        $response(["status" => 200, "message" => "Logged out"], 200);
    }
}
