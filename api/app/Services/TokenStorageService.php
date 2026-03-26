<?php

namespace App\Services;

class TokenStorageService
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function set($key, $value, $ttl = null)
    {
        $expires_at = (is_numeric($ttl) && $ttl > 0) ? (time() + (int)$ttl) : null;
        $data = ["key" => $key, "value" => $value, "expires_at" => $expires_at];
        $update = ["value" => $value, "expires_at" => $expires_at];
        // Use the QueryBuilder UPSERT method
        return $this->conn->quickbooks_db()->UPSERT("tokens", $data, $update);
    }

    public function get($key)
    {
        $results = $this->conn
            ->quickbooks_db()
            ->SELECT(['value', 'expires_at'], 'tokens')
            ->WHERE(['key' => $key])
            ->get();

        if (is_array($results) && count($results) > 0) {
            // Optionally, you can check for expiration here and return null if expired
            return $results[0]->value;
        }
        return null;
    }

    public function deleteExpired()
    {
        $this->conn
            ->quickbooks_db()
            ->DELETE('tokens')
            ->WHERE([
                'expires_at <' => time(),
                'expires_at IS NOT NULL' => null
            ]);
        // Note: The QueryBuilder executes on WHERE for DELETE.
    }
}
