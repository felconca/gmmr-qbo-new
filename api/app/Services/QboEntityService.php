<?php

namespace App\Services;

use QuickBooksOnlineHelper\Facades\QBO;
use Exception;

class QboEntityService

{
    protected $db;
    protected $companyId;

    public function __construct($db, $companyId)
    {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    public function vat($type = "vat-s")
    {
        switch ($type) {
            case "vat-s":
                return 15;
            case "vat-ex":
                return 16;
            default:
                return null;
        }
    }
    public function radio($codes = 0, $itemid = 0)
    {
        // codes is base on radio items if no code found return the $itemid
        switch ($codes) {
            case 13:
                return 12;
            case 3:
                return 11;
            case 15:
                return 13;
            case 31:
                return 14;
            default:
                return $itemid;
        }
    }
    public function discountvat()
    {
        return 10; // sales tax
    }
    public function discount()
    {
        return 5; // sales account
    }
    public function synctoken($id, $token, $entity)
    {
        QBO::setAuth($this->companyId, $token);
        $invoice = QBO::get()->$entity($id);

        // Check for API call failure (status not 200 or 201)
        if (!is_array($invoice) || !isset($invoice['status']) || !in_array($invoice['status'], [200, 201], true)) {
            throw new Exception(
                isset($invoice['data']) && is_string($invoice['data'])
                    ? $invoice['data']
                    : "Failed to find invoice"
            );
        }

        return ["status" => $invoice['status'], "synctoken" => $invoice['data'][$entity]['SyncToken']];
    }
}
