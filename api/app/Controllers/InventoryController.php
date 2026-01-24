<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class InventoryController extends Rest
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
        $this->clientId = isset($_ENV["QBO_CLIENTID"]) ? $_ENV["QBO_CLIENTID"] : NULL;
        $this->secretId = isset($_ENV["QBO_SECRETID"]) ? $_ENV["QBO_SECRETID"] : NULL;
        $this->companyId = isset($_ENV["QBO_COMPANYID"]) ? $_ENV["QBO_COMPANYID"] : NULL;
    }

    public function index($request, $response, $params)
    {
        return $response(['message' => 'InventoryController index'], 200);
    }
    public function pharmacy($request, $response)
    {
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|numeric:min:1",
                "status" => "required|numeric:min:1",
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];
            $status = $input["status"];

            $invoices = $this->db->wgfinance()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo_id AS sent_id",

                    "p.sent_to_cost_qbo AS sent_status",
                    "p.sent_to_qbo_cost_id AS cost_id",
                    "p.sent_to_qbo_cost_date AS sent_date",
                    "p.sent_to_qbo_cost_amt AS booked_amt",
                    "p.sent_to_qbo_cost_update_amt AS updated_amt",

                    "p.TranStatus AS tstatus",

                    "SUM(pd.UnitCost) AS netcost",

                    "IFNULL(px.LastName, '') AS lname",
                    "IFNULL(px.MiddleName, '') AS mname",
                    "IFNULL(px.FirstName, '') AS fname",
                    "IFNULL(px.namesuffix, '') AS suffix",

                    "CONCAT(px.LastName, ', ', px.FirstName) AS lnamefirst",
                    "CONCAT(px.FirstName, ', ', px.LastName) AS fnamefirst",
                    "CONCAT(px.LastName, ', ', px.FirstName, IFNULL(px.namesuffix,'')) AS lnamefirstsx",
                    "CONCAT(px.FirstName, ', ', px.LastName, IFNULL(px.namesuffix,'')) AS fnamefirstsx",
                    "CONCAT(px.FirstName, ' ', IFNULL(SUBSTRING(px.MiddleName,1,1),''), ' ', px.LastName, ' ', IFNULL(px.namesuffix,'')) AS completepx",

                    "px.qbo_px_id AS qbopx",

                    "ux.FirstName AS ufname",
                    "ux.LastName AS ulname",

                    "ltf.TranStatusDescription AS transtatus"
                ], "possales_details pd")
                ->LEFTJOIN("possales p", "p.TranRID = pd.TranRID")
                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("wgcentralsupply.lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE("(p.pinnedby >= 0 OR p.bookedbycashier > 0)")
                ->WHERE("pd.UnitCost > 0")
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($status > 0) {
                $invoices->WHERE(["p.TranStatus" => $status]);
            } else {
                $invoices->WHERE_IN("p.TranStatus", [9, 10, 12]);
            }

            if ($isbooked != -1) {
                $invoices->WHERE(["p.sent_to_cost_qbo" => $isbooked]);
            }

            $rows = $invoices->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }

    public function nonpharma($request, $response)
    {
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|numeric:min:1",
                "status" => "required|numeric:min:1",
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];
            $status = $input["status"];

            $invoices = $this->db->wgcentralsupply()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo_id AS sent_id",

                    "p.sent_to_cost_qbo AS sent_status",
                    "p.sent_to_qbo_cost_id AS cost_id",
                    "p.sent_to_qbo_cost_date AS sent_date",
                    "p.sent_to_qbo_cost_amt AS booked_amt",
                    "p.sent_to_qbo_cost_update_amt AS updated_amt",

                    "p.TranStatus AS tstatus",

                    "SUM(pd.UnitCost) AS netcost",

                    "IFNULL(px.LastName, '') AS lname",
                    "IFNULL(px.MiddleName, '') AS mname",
                    "IFNULL(px.FirstName, '') AS fname",
                    "IFNULL(px.namesuffix, '') AS suffix",

                    "CONCAT(px.LastName, ', ', px.FirstName) AS lnamefirst",
                    "CONCAT(px.FirstName, ', ', px.LastName) AS fnamefirst",
                    "CONCAT(px.LastName, ', ', px.FirstName, IFNULL(px.namesuffix, '')) AS lnamefirstsx",
                    "CONCAT(px.FirstName, ', ', px.LastName, IFNULL(px.namesuffix, '')) AS fnamefirstsx",
                    "CONCAT(px.FirstName, ' ', IFNULL(SUBSTRING(px.MiddleName,1,1),''), ' ', px.LastName, ' ', IFNULL(px.namesuffix, '')) AS completepx",

                    "px.qbo_px_id AS qbopx",

                    "ux.FirstName AS ufname",
                    "ux.LastName AS ulname",

                    "ltf.TranStatusDescription AS transtatus"
                ], "possales_details pd")
                ->LEFTJOIN("possales p", "p.TranRID = pd.TranRID")
                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("wgcentralsupply.lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE("(p.pinnedby > 0 OR p.bookedbycashier > 0)")
                ->WHERE("pd.UnitCost > 0")
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($status > 0) {
                $invoices->WHERE(["p.TranStatus" => $status]);
            } else {
                $invoices->WHERE_IN("p.TranStatus", [1, 4, 5, 6, 16, 21]);
            }

            if ($isbooked != -1) {
                $invoices->WHERE(["p.sent_to_cost_qbo" => $isbooked]);
            }

            $rows = $invoices->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
    public function book_inventory($request, $response)
    {
        try {
            $qboService = new QboCustomerService($this->db, $this->companyId);
            $invoiceService = new InvoicesService($this->db);

            $input = $request->validate([
                "data"             => "required|array|min:1",
                "token"            => "required",
                "database"         => "required",
                'data.*.tranid'    => 'required|int|min:1',
                'data.*.pxid'      => 'required|int|min:1',
                'data.*.docnumber' => 'required|string',
                'data.*.txndate'   => 'required|date',
                'data.*.amount'    => 'required|float',
                'data.*.customerref' => 'numeric',
                'data.*.fname'       => 'required|string',
                'data.*.lname'       => 'required|string',
                'data.*.qbostatus'   => 'numeric',
                'data.*.qboid'       => 'numeric',
                'data.*.note'        => 'string',
                'data.*.mname'       => 'string',
                'data.*.suffix'      => 'string',
            ]);

            $inventory = $input["data"];
            $token = $input["token"];
            $db = $input['database'] === "wgfinance" ? "wgfinance" : "wgcentralsupply";
            $hasErrors = false;
            $results = [];


            foreach ($inventory as $row) {
                QBO::setAuth($this->companyId, $token);
                $updateData = [
                    "tranid" => $row["tranid"],
                    "amount" => $row["amount"],
                    "qboid"  => 0,
                ];

                try {
                    $qbo = new QboEntityService($this->db, $this->companyId);
                    $qbostatus = isset($row['qbostatus']) ? $row['qbostatus'] : 0;
                    $qboid = isset($row['qboid']) ? $row['qboid'] : 0;

                    $isUpdate = $qboid > 0 && ($qbostatus == 1 || $qbostatus == 2); // if already sent or modified make isUpdate true
                    $action = $isUpdate ? QBO::update() : QBO::create(); // isUpdate true use update else use create

                    $line = $this->line_inventory($row["tranid"], $input['database']);

                    if (isset($row['pxid']) && $row['pxid'] == 0) {
                        $customer = 530;
                    } elseif (isset($row['customerref']) && $row['customerref'] > 0) {
                        $customer = $row['customerref'];
                    } else {
                        $customer = $qboService->createCustomer([
                            "token"  => $token,
                            "pxid"   => $row["pxid"],
                            "fname"  => $row["fname"],
                            "lname"  => $row["lname"],
                            "mname"  => isset($row["mname"]) ? $row["mname"] : null,
                            "suffix" => isset($row["suffix"]) ? $row["suffix"] : null,
                        ]);
                    }


                    $inventory = [
                        "DocNumber" => $row["docnumber"],
                        "TxnDate" => $row["txndate"],
                        "Line" => $line,
                        "PrivateNote" => $row["note"],
                    ];
                    // return $response($inventory, 200);
                    if ($isUpdate) {
                        // FIX: set 'Id' to QBO Invoice id (not to $qbo service), 'sparse' must be true, 'SyncToken' is required
                        $inventory['Id'] = $qboid; // NOT $qbo (service), should be the QBO invoice id
                        $inventory['sparse'] = true;
                        $synctoken = $qbo->synctoken($row["qboid"], $token, "JournalEntry");

                        if ($synctoken) {
                            $inventory["SyncToken"] = $synctoken['synctoken'];
                        } else {
                            // Protect, must have SyncToken for update
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                    }

                    $result = $action->JournalEntry($inventory);

                    if (!is_array($result) || !isset($result['status']) || !in_array($result['status'], [200, 201], true)) {
                        // Mark as failed
                        $updateData["status"] = 4;
                        $updateData["qboid"] = $isUpdate ? $qboid : 0;
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "failed",
                            "error" => isset($result['data']) ? $result['data'] : "Unknown error"
                        ];
                        $hasErrors = true;
                    } else {
                        // Mark as success
                        $updateData["status"] = $qboid == 0 ? 1 : 2;
                        $updateData["qboid"] = isset($result["data"]["JournalEntry"]["Id"]) ? $result["data"]["JournalEntry"]["Id"] : ($qboid ?: null);
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success",
                            "qboid" => $updateData["qboid"]
                        ];
                    }

                    // Always update DB
                    $invoiceService->update_inventory($updateData,  $db);
                } catch (Exception $e) {
                    // Catch QBO errors / customer creation errors
                    $updateData["status"] = 4;
                    $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
                    $invoiceService->update_inventory($updateData, $db);

                    $results[] = [
                        "tranid" => $row["tranid"],
                        "status" => "failed",
                        "error" => $e->getMessage()
                    ];
                    $hasErrors = true;
                }
            }

            return $response([
                "status" => $hasErrors ? 400 : 200,
                "results" => $results
            ], $hasErrors ? 400 : 200);
        } catch (Exception $e) {
            return $response([
                "status" => 400,
                "error" => $e->getMessage()
            ], 400);
        }
    }
    public function delete_inventory($request, $response, $params)
    {
        $invoiceService = new InvoicesService($this->db);

        try {
            $input = $request->validate([
                "data"               => "required|array|min:1",
                "token"              => "required",
                "database"           => "required",
                'data.*.tranid'      => 'required|int|min:1',
                'data.*.qboid'       => 'required',
            ]);

            $inventory = $input["data"];
            $token = $input["token"];
            $db = $input['database'] === "wgfinance" ? "wgfinance" : "wgcentralsupply";
            $results = [];
            $hasErrors = false;

            foreach ($inventory as $row) {
                try {
                    $qbo = new QboEntityService($this->db, $this->companyId);
                    $synctoken = $qbo->synctoken($row["qboid"], $token, "JournalEntry");
                    $deleteResult = QBO::delete()->JournalEntry($row["qboid"], $synctoken['synctoken']);

                    $updateData = [
                        "tranid" => $row["tranid"],
                        "amount" => 0,
                        "qboid"  => 0,
                        "status" => 5
                    ];

                    // Check deleteResult for error handling (assume structure similar to QBO response)
                    if (
                        !is_array($deleteResult) ||
                        !isset($deleteResult['status']) ||
                        ($deleteResult['status'] !== 200 && $deleteResult['status'] !== 201)
                    ) {
                        $hasErrors = true;
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "failed",
                            "error" => isset($deleteResult['data']) ? $deleteResult['data'] : "Failed to delete in QBO"
                        ];
                    } else {
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success"
                        ];
                    }

                    $invoiceService->update_inventory($updateData, $db);
                } catch (\Exception $ex) {
                    $hasErrors = true;
                    $results[] = [
                        "tranid" => $row["tranid"],
                        "status" => "failed",
                        "error" => $ex->getMessage()
                    ];
                }
            }

            return $response([
                "status" => $hasErrors ? 400 : 200,
                "results" => $results
            ], $hasErrors ? 400 : 200);
        } catch (Exception $e) {
            return $response([
                "status" => 400,
                "error" => $e->getMessage()
            ], 400);
        }
    }



    public function line_inventory($id, $db)
    {
        // This implementation has issues:
        // 1. The variables $debit and $credit are not initialized as arrays, and only store the last $list item.
        // 2. The foreach loop processes $list, but then OUTSIDE the loop only the last $list is used.
        // 3. The spread operator (`...`) only works for arrays (PHP 7.4+), and here $debit and $credit are arrays with only one element, and only for the last $list.

        // A corrected, minimal rewrite would be:

        $invoiceService = new InvoicesService($this->db);
        $details = $db == 'wgfinance' ? $invoiceService->pharmacy_line($id) : $invoiceService->nonpharma_line($id);

        $qbo = new QboEntityService($this->db, $this->companyId);

        $credit = [];
        $debit = [];

        foreach ($details as $list) {
            // Ensure $list is an array, not an object (stdClass)
            if (is_object($list)) {
                $list = (array)$list;
            }

            // Skip items where cost is not set or cost is zero
            if (!isset($list["cost"]) || floatval($list["cost"]) == 0) {
                continue;
            }

            $credit[] = [
                "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                    "PostingType" => "Credit",
                    "AccountRef" => ["value" => $qbo->radio_inventory(isset($list["codes"]) ? $list["codes"] : 0, isset($list["invid"]) ? $list["invid"] : 0)],
                ],
                "Amount" => (isset($list["cost"]) ? $list["cost"] : 0) * (isset($list["qty"]) ? $list["qty"] : 0),
            ];
            $debit[] = [
                "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
                "DetailType" => "JournalEntryLineDetail",
                "JournalEntryLineDetail" => [
                    "PostingType" => "Debit",
                    "AccountRef" => ["value" => $qbo->radio_cost(isset($list["codes"]) ? $list["codes"] : 0, isset($list["costid"]) ? $list["costid"] : 0)],
                ],
                "Amount" => (isset($list["cost"]) ? $list["cost"] : 0) * (isset($list["qty"]) ? $list["qty"] : 0),
            ];
        }

        $line = array_merge($debit, $credit);
        return $line;
    }
}
