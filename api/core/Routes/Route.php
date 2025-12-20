<?php

namespace Core\Routes;

class Route
{
    // Removed type hints for PHP 5.6 compatibility
    private static $routes = array();
    private static $groupStack = array(); // Stack to track nested groups

    // Add GET route
    public static function get($path, $handler, $middleware = array())
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    // Add POST route
    public static function post($path, $handler, $middleware = array())
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    public static function put($path, $handler, $middleware = array())
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function delete($path, $handler, $middleware = array())
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    // Return all routes
    public static function all()
    {
        return self::$routes;
    }

    // Grouping function
    public static function group($attributes, $callback)
    {
        // Push current group attributes to stack
        self::$groupStack[] = $attributes;

        // Execute the callback (user defines routes inside)
        $callback();

        // Pop after callback ends
        array_pop(self::$groupStack);
    }

    // Internal route addition considering current group(s)
    private static function addRoute($method, $path, $handler, $middleware)
    {
        $prefix = '';
        $groupMiddleware = array();

        // Apply all nested group attributes
        foreach (self::$groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware']) && is_array($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, $group['middleware']);
            }
        }

        // Combine group prefix and route path
        $fullPath = trim($prefix . '/' . trim($path, '/'), '/');

        // Merge middleware
        $allMiddleware = array_merge($groupMiddleware, $middleware);

        self::$routes[] = array($method, $fullPath, $handler, $allMiddleware);
    }
}
