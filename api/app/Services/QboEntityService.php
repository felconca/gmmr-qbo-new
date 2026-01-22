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

    public function consultation($productid = 0, $vat = 0, $itemid = 0)
    {
        if ($productid == 436 && $vat == 0) {
            return "41";
        }
        if ($productid == 436 && $vat > 0) {
            return "42";
        } else {
            return  $itemid;
        }
    }
    public function pharmacy($class = 0, $vat = 0, $itemid = 0)
    {
        if ($class == 94) {
            return "44";
        }
        return $vat > 0 ? "40" : $itemid;
    }

    public function radio_cost($codes = 0, $costid = 0)
    {
        // $id = $this->jnrid($codes);
        // return ($codes > 0 && is_array($id) && isset($id["costid"])) ? $id["costid"] : $costid;
        switch ($codes) {
            case 13: //RADIOLOGY
                return 281;
            case 3: //CT-SCAN
                return 294;
            case 15: // ULTRASOUND
                return 283;
            case 31: //MRI
                return 284;
            default:
                return $costid;
        }
    }

    public function radio_inventory($codes = 0, $invid = 0)
    {
        // $id = $this->jnrid($codes);
        // return ($codes > 0 && is_array($id) && isset($id["invid"])) ? $id["invid"] : $invid;
        switch ($codes) {
            case 13: //RADIOLOGY
                return 262;
            case 3: //CT-SCAN
                return 250;
            case 15: // ULTRASOUND
                return 263;
            case 31: //MRI
                return 285;
            default:
                return $invid;
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
        $qb = QBO::get()->$entity($id);

        // Check for API call failure (status not 200 or 201)
        if (!is_array($qb) || !isset($qb['status']) || !in_array($qb['status'], [200, 201], true)) {
            throw new Exception(
                isset($qb['data']) && is_string($qb['data'])
                    ? $qb['data']
                    : "Failed to find $entity"
            );
        }

        return ["status" => $qb['status'], "synctoken" => $qb['data'][$entity]['SyncToken']];
    }
    public function details($id, $token, $entity)
    {
        QBO::setAuth($this->companyId, $token);
        $qb = QBO::get()->$entity($id);

        // Check for API call failure (status not 200 or 201)
        if (!is_array($qb) || !isset($qb['status']) || !in_array($qb['status'], [200, 201], true)) {
            throw new Exception(
                isset($qb['data']) && is_string($qb['data'])
                    ? $qb['data']
                    : "Failed to find $entity"
            );
        }

        return ["status" => $qb['status'], "details" => $qb['data'][$entity]];
    }

    // public function jnrid($id)
    // {
    //     // Return cost and inventory IDs for a given department code
    //     $department = $this->conn->wgcentralsupply()
    //         ->SELECT("centerRID", 'department')
    //         ->WHERE(["DeptCode" => $id])
    //         ->first();

    //     if (!$department) {
    //         return null;
    //     }

    //     $center = $this->conn->iparbg()
    //         ->SELECT(["qbo_cost_id AS costid", "qbo_inv_id AS invid"], 'lkup_centers')
    //         ->WHERE(["centerRID" => $department->centerRID])
    //         ->first();

    //     if (!$center) {
    //         return null;
    //     }

    //     return [
    //         "costid" => $center->costid,
    //         "invid"  => $center->invid
    //     ];
    // }
}
