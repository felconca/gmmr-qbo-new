<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Http/Http.php';

use Core\Requests\Request;
use Dotenv\Dotenv;
use Core\Routes\Route;
use Includes\Rest;

// For PHP 5.6+ compatibility - no type hints or trailing commas, use array()
// $dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/..');
// $dotenv->load();
// $_CONFIG = require __DIR__ . '/../config/env.php';

$env = require __DIR__ . '/../config/env.php';

// Populate $_ENV
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}

// (Optional but recommended) Populate getenv()
foreach ($_ENV as $key => $value) {
    putenv($key . '=' . $value);
}

// // Optional: allow real env vars to override config
// if (!empty($_ENV)) {
//     foreach ($_ENV as $key => $value) {
//         if (isset($_CONFIG[$key])) {
//             $_CONFIG[$key] = $value;
//         }
//     }
// }

// // Make it globally accessible
// $GLOBALS['_CONFIG'] = $_CONFIG;

// // Optional: prevent accidental overwrite
// unset($_CONFIG);

// Load all defined routes
$routes = Route::all();

// Read incoming request info
$requestPath = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$segments = $requestPath ? explode('/', $requestPath) : array();
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

$matched         = false;
$params          = array();
$routeMiddleware = array();
$controller      = null;
$handler         = null;

// ROUTE MATCHING LOOP
foreach ($routes as $route) {
    // Use list() as in PHP 5.6, and expect array indices
    list($routeMethod, $pattern, $handlerDef, $middleware) = $route;

    if ($method !== $routeMethod) {
        continue;
    }

    // Pattern matching
    $patternSegments = explode('/', trim($pattern, '/'));

    if (count($patternSegments) === count($segments)) {
        $match = true;
        $params = array();

        for ($i = 0; $i < count($patternSegments); $i++) {
            if (preg_match('/^{.*}$/', $patternSegments[$i])) {
                $paramName = trim($patternSegments[$i], '{}');
                $params[$paramName] = $segments[$i];
            } elseif ($patternSegments[$i] !== $segments[$i]) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $matched = true;

            // Extract controller + method
            $handlerExploded = explode('@', $handlerDef);
            $controllerName = isset($handlerExploded[0]) ? $handlerExploded[0] : '';
            $methodName = isset($handlerExploded[1]) ? $handlerExploded[1] : '';
            $controllerClass = "App\\Controllers\\$controllerName";

            // Validate controller
            if (!class_exists($controllerClass)) {
                header("HTTP/1.1 500 Internal Server Error");
                echo json_encode(array('error' => "Controller $controllerClass not found"));
                exit;
            }

            $controller = new $controllerClass();

            // Validate method
            if (!method_exists($controller, $methodName)) {
                header("HTTP/1.1 500 Internal Server Error");
                echo json_encode(array('error' => "Method $methodName not found in $controllerClass"));
                exit;
            }

            // Build REST helper (request + response)
            $rest = new Rest();
            $requestData = $rest->inputs();
            $request = new Request($requestData);
            $paramRequest = new Request($params);  // <-- wrap params as Request

            // PHP 5.6 compatible closure
            $response = function ($data, $status = 200) use ($rest) {
                return $rest->response($data, $status);
            };

            // Handler closure for 5.6 (no type hints)
            $handler = function () use ($controller, $methodName, $request, $response, $paramRequest) {
                return $controller->$methodName($request, $response, $paramRequest);
            };

            // Make sure middleware is always array()
            $routeMiddleware = is_array($middleware) ? $middleware : array();
            break;
        }
    }
}

// Route not found
if (!$matched) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(array('error' => 'Route not found'));
    exit;
}

// APPLY MIDDLEWARE (chain)
foreach ($routeMiddleware as $mw) {
    $prevHandler = $handler;
    $handler = function () use ($mw, $controller, $prevHandler) {
        return $mw->handle($controller, $prevHandler);
    };
}

// Execute final handler (no parentheses after closure definition in 5.6)
$handler();
