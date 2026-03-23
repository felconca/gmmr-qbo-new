<?php

namespace App\Services;

use Exception;
use QuickBooksOnlineHelper\Facades\QBO;

class TokenStorageService
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function set($key, $value, $ttl = null)
    {
        $expires_at = $ttl ? time() + $ttl : null;
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
        return $results;
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
