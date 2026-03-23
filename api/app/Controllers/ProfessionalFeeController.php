<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class ProfessionalFeeController extends Rest
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
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|numeric:min:1",
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];

            $invoices = $this->db->wgcentralsupply()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo AS sent_status",
                    "p.sent_to_qbo_id AS sent_id",
                    "p.sent_to_qbo_date AS sent_date",
                    "p.sent_to_qbo_amt AS booked_amt",
                    "p.sent_to_qbo_update_amt AS updated_amt",
                    "p.TranStatus AS tstatus",

                    "SUM(pd.line_Discount) AS ldiscount",
                    "SUM(pd.DiscountApplied) AS discount",
                    "SUM(pd.line_netofvat) AS netofvat",
                    "SUM(pd.VatAmnt) AS vat",
                    "SUM(pd.GrossLine) AS gross",
                    "SUM(pd.ExtendAmount) AS netamount",

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
                ->LEFTJOIN("lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("(p.pinnedby > 0 OR p.bookedbycashier > 0)")
                ->WHERE("p.ApprovedBy = 0")
                ->WHERE("p.NetAmountDue != 0")
                ->WHERE("p.TranStatus = 17")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($isbooked != -1) {
                $invoices->WHERE(["p.sent_to_qbo" => $isbooked]);
            }

            $rows = $invoices->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }

    public function edit($request, $response, $params)
    {
        try {
            $invoiceService = new InvoicesService($this->db);
            $input = $request->validate([
                "id" => "required",
            ]);
            $invoice = $this->db->wgcentralsupply()
                ->SELECT([
                    "p.TranRID AS tranid",
                    "p.PxRID AS pxid",
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo AS sent_status",
                    "p.TotalDiscounts AS ldiscount",
                    "p.TotalSCPWDDiscounts AS discount",
                    "p.NetOfVAT AS netofvat",
                    "p.TotalVat AS vat",
                    "p.GrossAmountDue AS gross",
                    "p.NetAmountDue AS netamount",


                    "px.LastName AS pxlname",
                    "px.MiddleName AS pxmname",
                    "px.FirstName AS pxfname",
                    "px.namesuffix AS suffix",

                    "ux.FirstName AS ufname",
                    "ux.LastName AS ulname",
                    "ltf.TranStatusDescription AS transtatus"
                ], "possales p")

                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE(["p.TranRID" => $input['id']])->first();
            if ($invoice) {
                $details = $invoiceService->pf_line($input["id"]);
                if ($details) {
                    $data = [
                        "invoice" => $invoice,
                        "details" => $details
                    ];
                    return $response($data, 200);
                } else {
                    return $response(['message' => "notfound"], 404);
                }
            } else {
                return $response(['message' => "notfound"], 404);
            }
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }

    // qbo functions
    // public function book_invoice($request, $response, $params)
    // {
    //     try {
    //         $customers = new QboCustomerService($this->db, $this->companyId);
    //         $invoiceService = new InvoicesService($this->db);

    //         $input = $request->validate([
    //             "data"             => "required|array|min:1",
    //             "token"            => "required",
    //             'data.*.tranid'    => 'required|int|min:1',
    //             'data.*.pxid'      => 'required|int|min:1',
    //             'data.*.docnumber' => 'required|string',
    //             'data.*.txndate'   => 'required|date',
    //             'data.*.amount'    => 'required|float',
    //             'data.*.gtaxcalc'  => 'required|string',
    //             'data.*.customerref' => 'numeric',
    //             'data.*.fname'       => 'required|string',
    //             'data.*.lname'       => 'required|string',
    //             'data.*.qbostatus'   => 'numeric',
    //             'data.*.qboid'       => 'numeric',
    //             'data.*.memo'        => 'string',
    //             'data.*.gstatus'     => 'string',
    //             'data.*.mname'       => 'string',
    //             'data.*.suffix'      => 'string',
    //         ]);

    //         $invoices = $input["data"];
    //         $token = $input["token"];
    //         $hasErrors = false;
    //         $results = [];

    //         // get only those pxid where customerref is equal to 0
    //         $pxidMap = [];
    //         foreach ($invoices as $invoice) {
    //             $customerref = isset($invoice['customerref']) ? (int)$invoice['customerref'] : 0;
    //             if ((int)$invoice['pxid'] > 0 && $customerref === 0) {
    //                 $pxid = $invoice['pxid'];
    //                 if (!isset($pxidMap[$pxid])) { // first occurrence wins
    //                     $pxidMap[$pxid] = [
    //                         "pxid"   => $pxid,
    //                         "fname"  => $invoice["fname"],
    //                         "lname"  => $invoice["lname"],
    //                         "mname"  => isset($invoice["mname"]) ? $invoice["mname"] : null,
    //                         "suffix" => isset($invoice["suffix"]) ? $invoice["suffix"] : null,
    //                     ];
    //                 }
    //             }
    //         }
    //         $unresolvedPatients = array_values($pxidMap);
    //         $resolvedCustomers  = $customers->create($unresolvedPatients, $token);

    //         // authenticate qbo and manage service for booking
    //         QBO::setAuth($this->companyId, $token);
    //         $qbo = new QboEntityService($this->db, $this->companyId);

    //         $tranids = array_column($invoices, 'tranid');
    //         $allLines = $this->line_invoice($tranids);

    //         foreach ($invoices as $row) {

    //             $updateData = [
    //                 "tranid" => $row["tranid"],
    //                 "amount" => $row["amount"],
    //                 "qboid"  => 0,
    //             ];

    //             try {
    //                 // $qbostatus = isset($row['qbostatus']) ? $row['qbostatus'] : 0;
    //                 $qboid = isset($row['qboid']) ? $row['qboid'] : 0;

    //                 $pxid        = (int)$row['pxid'];
    //                 $customerref = isset($row['customerref']) ? (int)$row['customerref'] : 0;

    //                 $isUpdate = $qboid > 0;
    //                 $action = $isUpdate ? QBO::update() : QBO::create();

    //                 // $line = $this->line_invoice($row["tranid"]);
    //                 $line = $allLines[$row['tranid']];


    //                 if ($pxid === 0) {
    //                     $customer = 530; // walk-in sentinel
    //                 } elseif ($customerref > 0) {
    //                     $customer = $customerref; // already resolved, passed from frontend
    //                 } elseif (isset($resolvedCustomers[$pxid])) {
    //                     $customer = $resolvedCustomers[$pxid]; // resolved via create()
    //                 } else {
    //                     // should never reach here if filtering was correct
    //                     throw new Exception("Customer could not be resolved for pxid: " . $pxid);
    //                 }

    //                 $invoice = [
    //                     "DocNumber" => $row["docnumber"],
    //                     "TxnDate" => $row["txndate"],
    //                     "TotalAmt" => $row["amount"],
    //                     "Line" => $line,
    //                     "CustomerRef" => ["value" => $customer],
    //                     "GlobalTaxCalculation" => $row["gtaxcalc"],
    //                     "CustomerMemo" => ["value" => isset($row['memo']) ? $row['memo'] : ''],
    //                     "CustomField" => [
    //                         [
    //                             "DefinitionId" => "1",
    //                             "Name" => "Patient ID",
    //                             "Type" => "StringType",
    //                             "StringValue" => $row["pxid"]
    //                         ],
    //                         [
    //                             "DefinitionId" => "2",
    //                             "Name" => "GMMR Status",
    //                             "Type" => "StringType",
    //                             "StringValue" => $row["gstatus"]
    //                         ]
    //                     ],
    //                     "domain" => "QBO",
    //                     "PrintStatus" => "NeedToPrint",
    //                     "CurrencyRef" => ["value" => "PHP", "name" => "Philippine Peso"],
    //                 ];

    //                 if ($isUpdate) {
    //                     // FIX: set 'Id' to QBO Invoice id (not to $qbo service), 'sparse' must be true, 'SyncToken' is required
    //                     $invoice['Id'] = $qboid; // NOT $qbo (service), should be the QBO invoice id
    //                     $invoice['sparse'] = true;
    //                     $synctoken = $qbo->synctoken($row["qboid"], $token, "Invoice", true);

    //                     if ($synctoken) {
    //                         $invoice["SyncToken"] = $synctoken['synctoken'];
    //                     } else {
    //                         // Protect, must have SyncToken for update
    //                         throw new \Exception("SyncToken missing for QBO update");
    //                     }
    //                 }

    //                 $result = $action->Invoice($invoice);

    //                 if (!is_array($result) || !isset($result['status']) || !in_array($result['status'], [200, 201], true)) {
    //                     // Mark as failed
    //                     $updateData["status"] = 4;
    //                     $updateData["qboid"] = $isUpdate ? $qboid : 0;
    //                     $results[] = [
    //                         "tranid" => $row["tranid"],
    //                         "status" => "failed",
    //                         "error" => isset($result['data']) ? $result['data'] : "Unknown error"
    //                     ];
    //                     $hasErrors = true;
    //                 } else {
    //                     // Mark as success
    //                     $updateData["status"] = $qboid == 0 ? 1 : 2;
    //                     $updateData["qboid"] = isset($result["data"]["Invoice"]["Id"]) ? $result["data"]["Invoice"]["Id"] : ($qboid ?: null);
    //                     $results[] = [
    //                         "tranid" => $row["tranid"],
    //                         "status" => "success",
    //                         "qboid" => $updateData["qboid"]
    //                     ];
    //                 }

    //                 // Always update DB
    //                 $invoiceService->update($updateData, "wgcentralsupply");
    //             } catch (Exception $e) {
    //                 // Catch QBO errors / customer creation errors
    //                 $updateData["status"] = 4;
    //                 $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
    //                 $invoiceService->update($updateData, "wgcentralsupply");

    //                 $results[] = [
    //                     "tranid" => $row["tranid"],
    //                     "status" => "failed",
    //                     "error" => $e->getMessage()
    //                 ];
    //                 $hasErrors = true;
    //             }
    //         }

    //         // Return overall result
    //         return $response([
    //             "status" => $hasErrors ? 400 : 200,
    //             "results" => $results
    //         ], $hasErrors ? 400 : 200);
    //     } catch (Exception $e) {
    //         return $response([
    //             "status" => 400,
    //             "error" => $e->getMessage()
    //         ], 400);
    //     }
    // }

    public function book_invoice($request, $response, $params)
    {
        try {
            $customers      = new QboCustomerService($this->db, $this->companyId);
            $invoiceService = new InvoicesService($this->db);

            $input = $request->validate([
                "data"               => "required|array|min:1",
                "token"              => "required",
                'data.*.tranid'      => 'required|int|min:1',
                'data.*.pxid'        => 'required|int|min:1',
                'data.*.docnumber'   => 'required|string',
                'data.*.txndate'     => 'required|date',
                'data.*.amount'      => 'required|float',
                'data.*.gtaxcalc'    => 'required|string',
                'data.*.customerref' => 'numeric',
                'data.*.fname'       => 'required|string',
                'data.*.lname'       => 'required|string',
                'data.*.qbostatus'   => 'numeric',
                'data.*.qboid'       => 'numeric',
                'data.*.memo'        => 'string',
                'data.*.gstatus'     => 'string',
                'data.*.mname'       => 'string',
                'data.*.suffix'      => 'string',
            ]);

            $invoices  = $input["data"];
            $token     = $input["token"];
            $hasErrors = false;
            $results   = [];

            // Phase 1: Resolve unresolved customers (customerref = 0) — one batch
            $pxidMap = [];
            foreach ($invoices as $invoice) {
                $customerref = isset($invoice['customerref']) ? (int)$invoice['customerref'] : 0;
                if ((int)$invoice['pxid'] > 0 && $customerref === 0) {
                    $pxid = $invoice['pxid'];
                    if (!isset($pxidMap[$pxid])) {
                        $pxidMap[$pxid] = [
                            "pxid"   => $pxid,
                            "fname"  => $invoice["fname"],
                            "lname"  => $invoice["lname"],
                            "mname"  => isset($invoice["mname"]) ? $invoice["mname"] : null,
                            "suffix" => isset($invoice["suffix"]) ? $invoice["suffix"] : null,
                        ];
                    }
                }
            }
            $unresolvedPatients = array_values($pxidMap);
            $resolvedCustomers  = !empty($unresolvedPatients)
                ? $customers->create($unresolvedPatients, $token)
                : [];

            // Phase 2: Auth QBO once, batch fetch lines
            QBO::setAuth($this->companyId, $token);
            $qbo      = new QboEntityService($this->db, $this->companyId);
            $tranids  = array_column($invoices, 'tranid');
            $allLines = $this->line_invoice($tranids);

            // Phase 3: Book all invoices — collect results, no DB writes yet
            $pendingUpdates = [];

            foreach ($invoices as $row) {
                $qboid       = isset($row['qboid']) ? (int)$row['qboid'] : 0;
                $pxid        = (int)$row['pxid'];
                $customerref = isset($row['customerref']) ? (int)$row['customerref'] : 0;
                $isUpdate    = $qboid > 0;
                $action      = $isUpdate ? QBO::update() : QBO::create();
                $line        = $allLines[$row['tranid']];

                try {
                    if ($pxid === 0) {
                        $customer = 530;
                    } elseif ($customerref > 0) {
                        $customer = $customerref;
                    } elseif (isset($resolvedCustomers[$pxid])) {
                        $customer = $resolvedCustomers[$pxid];
                    } else {
                        throw new Exception("Customer could not be resolved for pxid: " . $pxid);
                    }

                    $invoice = [
                        "DocNumber"           => $row["docnumber"],
                        "TxnDate"             => $row["txndate"],
                        "TotalAmt"            => $row["amount"],
                        "Line"                => $line,
                        "CustomerRef"         => ["value" => $customer],
                        "GlobalTaxCalculation" => $row["gtaxcalc"],
                        "CustomerMemo"        => ["value" => isset($row['memo']) ? $row['memo'] : ''],
                        "CustomField"         => [
                            [
                                "DefinitionId" => "1",
                                "Name"         => "Patient ID",
                                "Type"         => "StringType",
                                "StringValue"  => $row["pxid"]
                            ],
                            [
                                "DefinitionId" => "2",
                                "Name"         => "GMMR Status",
                                "Type"         => "StringType",
                                "StringValue"  => $row["gstatus"]
                            ]
                        ],
                        "domain"      => "QBO",
                        "PrintStatus" => "NeedToPrint",
                        "CurrencyRef" => ["value" => "PHP", "name" => "Philippine Peso"],
                    ];

                    if ($isUpdate) {
                        $invoice['Id']     = $qboid;
                        $invoice['sparse'] = true;
                        $synctoken         = $qbo->synctoken($row["qboid"], $token, "Invoice", true);

                        if (!$synctoken) {
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                        $invoice["SyncToken"] = $synctoken['synctoken'];
                    }

                    $result = $action->Invoice($invoice);

                    if (!is_array($result) || !isset($result['status']) || !in_array($result['status'], [200, 201], true)) {
                        $pendingUpdates[] = [
                            "tranid" => $row["tranid"],
                            "amount" => $row["amount"],
                            "qboid"  => $isUpdate ? $qboid : 0,
                            "status" => 4,
                        ];
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "failed",
                            "error"  => isset($result['data']) ? $result['data'] : "Unknown error"
                        ];
                        $hasErrors = true;
                    } else {
                        $newQboId = isset($result["data"]["Invoice"]["Id"])
                            ? $result["data"]["Invoice"]["Id"]
                            : ($qboid ?: null);

                        $pendingUpdates[] = [
                            "tranid" => $row["tranid"],
                            "amount" => $row["amount"],
                            "qboid"  => $newQboId,
                            "status" => $qboid == 0 ? 1 : 2,
                        ];
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success",
                            "qboid"  => $newQboId
                        ];
                    }
                } catch (Exception $e) {
                    $pendingUpdates[] = [
                        "tranid" => $row["tranid"],
                        "amount" => $row["amount"],
                        "qboid"  => $qboid > 0 ? $qboid : 0,
                        "status" => 4,
                    ];
                    $results[] = [
                        "tranid" => $row["tranid"],
                        "status" => "failed",
                        "error"  => $e->getMessage()
                    ];
                    $hasErrors = true;
                }
            }

            // Phase 4: One batch DB write for all results
            $invoiceService->update($pendingUpdates, "wgcentralsupply");

            return $response([
                "status"  => $hasErrors ? 400 : 200,
                "results" => $results
            ], $hasErrors ? 400 : 200);
        } catch (Exception $e) {
            return $response([
                "status" => 400,
                "error"  => $e->getMessage()
            ], 400);
        }
    }
    public function delete_invoice($request, $response, $params)
    {
        $invoiceService = new InvoicesService($this->db);

        try {
            $input = $request->validate([
                "data"               => "required|array|min:1",
                "token"              => "required",
                'data.*.tranid'      => 'required|int|min:1',
                'data.*.qboid'       => 'required',
            ]);

            $invoices = $input["data"];
            $token = $input["token"];
            $results = [];
            $hasErrors = false;

            foreach ($invoices as $row) {
                try {
                    $qbo = new QboEntityService($this->db, $this->companyId);
                    $synctoken = $qbo->synctoken($row["qboid"], $token, "Invoice");
                    $deleteResult = QBO::delete()->Invoice($row["qboid"], $synctoken['synctoken']);

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

                    $invoiceService->update($updateData, "wgcentralsupply");
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
    public function find_invoice($request, $response, $params)
    {
        $token = $request["token"];
        $id = $request["id"];
        $qbo = new QboEntityService($this->db, $this->companyId);
        $synctoken = $qbo->synctoken($id, $token, "Invoice");
        return $response($synctoken, $synctoken['status']);
    }
    // private function line_invoice($id)
    // {
    //     $invoiceService = new InvoicesService($this->db);
    //     $details = $invoiceService->pf_line($id);

    //     $lines = [];
    //     $qbo = new QboEntityService($this->db, $this->companyId);
    //     $index = 0;

    //     $grossTotal = 0;
    //     $discountTotal = 0;
    //     $ldiscountTotal = 0;

    //     foreach ($details as $list) {
    //         // Ensure $list is an array, not an object (stdClass)
    //         if (is_object($list)) {
    //             $list = (array)$list;
    //         }

    //         $lines[] = [
    //             "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
    //             "DetailType" => "SalesItemLineDetail",
    //             "SalesItemLineDetail" => [
    //                 "ItemRef" => ["value" => isset($list["itemid"]) ? $list["itemid"] : 0],
    //                 "Qty" => isset($list["qty"]) ? $list["qty"] : 1,
    //                 "UnitPrice" => isset($list["price"]) ? $list["price"] : 0,
    //                 "DiscountAmt" => (isset($list["ldiscount"]) ? $list["ldiscount"] : 0) + (isset($list["discount"]) ? $list["discount"] : 0),
    //             ],
    //             "LineNum" => $index + 1,
    //             "Amount" => isset($list["gross"]) ? $list["gross"] : 0,
    //         ];
    //         $index++;

    //         // Add to totals for subtotal and discounts
    //         $grossTotal += isset($list["gross"]) ? $list["gross"] : 0;
    //         $discountTotal += isset($list["discount"]) ? $list["discount"] : 0;
    //         $ldiscountTotal += isset($list["ldiscount"]) ? $list["ldiscount"] : 0;
    //     }

    //     // Add subtotal line
    //     $lines[] = [
    //         "LineNum" => count($details) + 1,
    //         "Description" => "Subtotal: PHP{$grossTotal}",
    //         "DetailType" => "DescriptionOnly",
    //     ];

    //     // Add discount line
    //     $discountSum = $discountTotal + $ldiscountTotal;
    //     $lines[] = [
    //         "Description" => "Line Discount & 20% Discount",
    //         "DetailType" => "SalesItemLineDetail",
    //         "SalesItemLineDetail" => [
    //             "Qty" => 0,
    //             "UnitPrice" => 0 - $discountSum,
    //             "ItemRef" => ["value" => $qbo->discount(), "name" => "Discount"],
    //         ],
    //         "LineNum" => count($details) + 2,
    //         "Amount" => 0 - $discountSum,
    //     ];
    //     // if ($discountSum != 0) {

    //     // }

    //     return $lines;
    // }
    private function line_invoice(array $tranids)
    {
        $invoiceService = new InvoicesService($this->db);
        $qbo            = new QboEntityService($this->db, $this->companyId);
        $discountItemId = $qbo->discount(); // called once

        // One query for all tranids
        $allDetails = $invoiceService->pf_line_batch($tranids);
        // returns rows with tranid column included

        // Group by tranid
        $grouped = [];
        foreach ($allDetails as $list) {
            $list    = is_object($list) ? (array)$list : $list;
            $tranid  = $list['tranid'];
            $grouped[$tranid][] = $list;
        }

        // Build lines per tranid
        $result = [];
        foreach ($grouped as $tranid => $details) {
            $lines          = [];
            $grossTotal     = 0;
            $discountTotal  = 0;
            $ldiscountTotal = 0;
            $index          = 0;

            foreach ($details as $list) {
                $lines[] = [
                    "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
                    "DetailType"  => "SalesItemLineDetail",
                    "SalesItemLineDetail" => [
                        "ItemRef"     => ["value" => isset($list["itemid"]) ? $list["itemid"] : 0],
                        "Qty"         => isset($list["qty"]) ? $list["qty"] : 1,
                        "UnitPrice"   => isset($list["price"]) ? $list["price"] : 0,
                        "DiscountAmt" => (isset($list["ldiscount"]) ? $list["ldiscount"] : 0)
                            + (isset($list["discount"]) ? $list["discount"] : 0),
                    ],
                    "LineNum" => $index + 1,
                    "Amount"  => isset($list["gross"]) ? $list["gross"] : 0,
                ];
                $index++;

                $grossTotal     += isset($list["gross"])     ? $list["gross"]     : 0;
                $discountTotal  += isset($list["discount"])  ? $list["discount"]  : 0;
                $ldiscountTotal += isset($list["ldiscount"]) ? $list["ldiscount"] : 0;
            }

            $discountSum = $discountTotal + $ldiscountTotal;

            $lines[] = [
                "LineNum"     => count($details) + 1,
                "Description" => "Subtotal: PHP{$grossTotal}",
                "DetailType"  => "DescriptionOnly",
            ];

            $lines[] = [
                "Description" => "Line Discount & 20% Discount",
                "DetailType"  => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "Qty"       => 0,
                    "UnitPrice" => 0 - $discountSum,
                    "ItemRef"   => ["value" => $discountItemId, "name" => "Discount"],
                ],
                "LineNum" => count($details) + 2,
                "Amount"  => 0 - $discountSum,
            ];

            $result[$tranid] = $lines;
        }

        return $result;
    }
}
