<?php

namespace Core\Database;

// For PHP 5.6+ (use array() instead of [])
class Database
{
    private $connections = array();

    public function __construct()
    {
        $host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
        $user = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root';
        $password = isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '';
        $dbConnectionsEnv = isset($_ENV['DB_CONNECTIONS']) ? $_ENV['DB_CONNECTIONS'] : '';
        $dbNames = array_filter(array_map('trim', explode(',', $dbConnectionsEnv)));

        // If no database names are listed, still allow a single fallback
        if (empty($dbNames)) {
            // array() for PHP 5.6 compatibility
            $dbNames = array(isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : '');
        }

        foreach ($dbNames as $dbName) {
            if ($dbName === '') continue;
            $conn = @new \mysqli($host, $user, $password, $dbName);
            if ($conn->connect_error) {
                $this->logError("Connection failed to $dbName: " . $conn->connect_error);
                $this->connections[$dbName] = null;
            } else {
                $this->connections[$dbName] = $conn;
            }
        }
    }

    /**
     * Traditional getter (still works)
     */
    public function getConnection($dbName = null)
    {
        // Use the first DB as default if none specified
        if ($dbName === null) {
            // PHP 5.6 compatible: no array_key_first, use manual reset
            $keys = array_keys($this->connections);
            $first = count($keys) ? $keys[0] : null;
            return $first ? isset($this->connections[$first]) ? new QueryBuilder($this->connections[$first]) : null : null;
        }

        if (!isset($this->connections[$dbName])) {
            $this->logError("No connection found for database: $dbName");
            return null;
        }

        return new QueryBuilder($this->connections[$dbName]);
    }

    /**
     * Magic property access: $db->marsdb
     */
    public function __get($name)
    {
        if (isset($this->connections[$name])) {
            return new QueryBuilder($this->connections[$name]);
        }

        $this->logError("No connection found for '$name'");
        return null;
    }

    /**
     * Magic call: $db->getConnection()->query("...") OR $db->marsdb->query("...")
     * Also supports $db->marsdb() style.
     */
    public function __call($name, $arguments)
    {
        // Allow calling like $db->marsdb()
        if (isset($this->connections[$name])) {
            return new QueryBuilder($this->connections[$name]);
        }

        // Or redirect to getConnection() if "getMarsdb" etc.
        if (strpos($name, 'get') === 0) {
            $dbName = lcfirst(substr($name, 3));
            if (isset($this->connections[$dbName])) {
                return new QueryBuilder($this->connections[$dbName]);
            }
        }

        $this->logError("Attempted to call undefined method or connection: $name");
        return null;
    }

    private function logError($message)
    {
        $logFile = __DIR__ . '/../../logs/database.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] ERROR: $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
