<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class CreditMemoController extends Rest
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
                "personType" => "string",
                "isbooked" => "required|numeric:min:1"
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];
            $personType = $input["personType"];

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
                    "px.PersonDataType AS persontype",

                    "ux.FirstName AS ufname",
                    "ux.LastName AS ulname",
                    "cm.CMRID AS cmid",

                    "ltf.TranStatusDescription AS transtatus"
                ], "possales_details pd")
                ->LEFTJOIN("possales p", "p.TranRID = pd.TranRID")
                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("credit_memo cm", "cm.TranRID = p.TranRID")
                ->LEFTJOIN("lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("(p.pinnedby > 0 OR p.bookedbycashier > 0)")
                ->WHERE("p.ApprovedBy = 0")
                ->WHERE("p.NetAmountDue != 0")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE("p.TranStatus = 22")
                ->WHERE("cm.creditto = 0")
                ->WHERE("cm.Deleted = 0")
                ->WHERE(["cm.Deleted" => 0])

                // ->WHERE_NOT_IN("px.PersonDataType", ['PATIENT', 'Patient', 'HMO', 'Corporate Acct', 'Health Facility', 'Assistance'])
                // ->WHERE_IN("px.PersonDataType", ['PATIENT', 'Patient', 'HMO', 'Corporate Acct', 'Health Facility', 'Assistance'])
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                // ->WHERE("p.Payment_for NOT LIKE '%salary%'")
                // ->WHERE("pd.payment_for NOT LIKE '%salary%'")
                ->WHERE("COALESCE(NULLIF(pd.payment_for, ''), p.Payment_for) NOT LIKE '%salary%'")
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($personType && $personType == "PATIENT") {
                $invoices->WHERE_IN("px.PersonDataType", ['PATIENT', 'Patient']);
            }
            if ($personType && $personType == "EMPLOYEE") {
                $invoices->WHERE_NOT_IN("px.PersonDataType", ['PATIENT', 'Patient', 'HMO', 'Corporate Acct', 'Health Facility', 'Assistance']);
            }
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
                    "cm.CMRID AS cmid",

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
                ->LEFTJOIN("credit_memo cm", "cm.TranRID = p.TranRID")
                ->WHERE(["p.TranRID" => $input['id']])->first();
            if ($invoice) {
                $details = $invoiceService->credit_line($input["id"]);
                if ($details) {
                    $data = [
                        "cm" => $invoice,
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
    public function book_credit($request, $response, $params)
    {
        try {
            $customers = new QboCustomerService($this->db, $this->companyId);
            $invoiceService = new InvoicesService($this->db);

            $input = $request->validate([
                "data"             => "required|array|min:1",
                "token"            => "required",
                'data.*.tranid'    => 'required|int|min:1',
                'data.*.pxid'      => 'required|int|min:1',
                'data.*.docnumber' => 'required|string',
                'data.*.txndate'   => 'required|date',
                'data.*.amount'    => 'required|float',
                'data.*.gtaxcalc'  => 'required|string',
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

            $credits = $input["data"];
            $token = $input["token"];
            $hasErrors = false;
            $results = [];

            // Phase 1: Resolve unresolved customers (customerref = 0) — one batch
            $pxidMap = [];
            foreach ($credits as $credit) {
                $customerref = isset($credit['customerref']) ? (int)$credit['customerref'] : 0;
                if ((int)$credit['pxid'] > 0 && $customerref === 0) {
                    $pxid = $credit['pxid'];
                    if (!isset($pxidMap[$pxid])) {
                        $pxidMap[$pxid] = [
                            "pxid"   => $pxid,
                            "fname"  => $credit["fname"],
                            "lname"  => $credit["lname"],
                            "mname"  => isset($credit["mname"]) ? $credit["mname"] : null,
                            "suffix" => isset($credit["suffix"]) ? $credit["suffix"] : null,
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
            $tranids  = array_column($credits, 'tranid');
            $allLines = $this->line_credit($tranids);

            // Phase 3: Book all credidts — collect results, no DB writes yet
            $pendingUpdates = [];
            foreach ($credits as $row) {
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

                    $credit = [
                        "DocNumber" => $row["docnumber"],
                        "TxnDate" => $row["txndate"],
                        "TotalAmt" => $row["amount"],
                        "Line" => $line,
                        "CustomerRef" => ["value" => $customer],
                        "GlobalTaxCalculation" => $row["gtaxcalc"],
                        "CustomerMemo" => ["value" => isset($row['memo']) ? $row['memo'] : ''],
                        "CustomField" => [
                            [
                                "DefinitionId" => "1",
                                "Name" => "Patient ID",
                                "Type" => "StringType",
                                "StringValue" => $row["pxid"]
                            ],
                            [
                                "DefinitionId" => "2",
                                "Name" => "GMMR Status",
                                "Type" => "StringType",
                                "StringValue" => $row["gstatus"]
                            ]
                        ],
                        "domain" => "QBO",
                        "PrintStatus" => "NeedToPrint",
                        "CurrencyRef" => ["value" => "PHP", "name" => "Philippine Peso"],
                    ];

                    if ($isUpdate) {
                        $credit['Id']     = $qboid;
                        $credit['sparse'] = true;
                        $synctoken         = $qbo->synctoken($row["qboid"], $token, "CreditMemo", true);

                        if (!$synctoken) {
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                        $credit["SyncToken"] = $synctoken['synctoken'];
                    }

                    $result = $action->CreditMemo($credit);

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
                        $newQboId = isset($result["data"]["CreditMemo"]["Id"])
                            ? $result["data"]["CreditMemo"]["Id"]
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
            $invoiceService->update($pendingUpdates, "wgcentralsupply");
            // // olds
            // foreach ($credits as $row) {
            //     QBO::setAuth($this->companyId, $token);
            //     $updateData = [
            //         "tranid" => $row["tranid"],
            //         "amount" => $row["amount"],
            //         "qboid"  => 0,
            //     ];

            //     try {
            //         $qbo = new QboEntityService($this->db, $this->companyId);
            //         $qbostatus = isset($row['qbostatus']) ? $row['qbostatus'] : 0;
            //         $qboid = isset($row['qboid']) ? $row['qboid'] : 0;

            //         $isUpdate = $qboid > 0;
            //         $action = $isUpdate ? QBO::update() : QBO::create();

            //         $line = $this->line_credit($row["tranid"]);

            //         if (isset($row['pxid']) && $row['pxid'] == 0) {
            //             $customer = 530;
            //         } elseif (isset($row['customerref']) && $row['customerref'] > 0) {
            //             $customer = $row['customerref'];
            //         } else {
            //             $customer = $qboService->createCustomer([
            //                 "token"  => $token,
            //                 "pxid"   => $row["pxid"],
            //                 "fname"  => $row["fname"],
            //                 "lname"  => $row["lname"],
            //                 "mname"  => isset($row["mname"]) ? $row["mname"] : null,
            //                 "suffix" => isset($row["suffix"]) ? $row["suffix"] : null,
            //             ]);
            //         }

            //         $credit = [
            //             "DocNumber" => $row["docnumber"],
            //             "TxnDate" => $row["txndate"],
            //             "TotalAmt" => $row["amount"],
            //             "Line" => $line,
            //             "CustomerRef" => ["value" => $customer],
            //             "GlobalTaxCalculation" => $row["gtaxcalc"],
            //             "CustomerMemo" => ["value" => isset($row['memo']) ? $row['memo'] : ''],
            //             "CustomField" => [
            //                 [
            //                     "DefinitionId" => "1",
            //                     "Name" => "Patient ID",
            //                     "Type" => "StringType",
            //                     "StringValue" => $row["pxid"]
            //                 ],
            //                 [
            //                     "DefinitionId" => "2",
            //                     "Name" => "GMMR Status",
            //                     "Type" => "StringType",
            //                     "StringValue" => $row["gstatus"]
            //                 ]
            //             ],
            //             "domain" => "QBO",
            //             "PrintStatus" => "NeedToPrint",
            //             "CurrencyRef" => ["value" => "PHP", "name" => "Philippine Peso"],
            //         ];

            //         if ($isUpdate) {
            //             // FIX: set 'Id' to QBO credit id (not to $qbo service), 'sparse' must be true, 'SyncToken' is required
            //             $credit['Id'] = $qboid; // NOT $qbo (service), should be the QBO credit id
            //             $credit['sparse'] = true;
            //             $synctoken = $qbo->synctoken($row["qboid"], $token, "CreditMemo");

            //             if ($synctoken) {
            //                 $credit["SyncToken"] = $synctoken['synctoken'];
            //             } else {
            //                 // Protect, must have SyncToken for update
            //                 throw new \Exception("SyncToken missing for QBO update");
            //             }
            //         }

            //         $result = $action->CreditMemo($credit);

            //         if (!is_array($result) || !isset($result['status']) || !in_array($result['status'], [200, 201], true)) {
            //             // Mark as failed
            //             $updateData["status"] = 4;
            //             $updateData["qboid"] = $isUpdate ? $qboid : 0;
            //             $results[] = [
            //                 "tranid" => $row["tranid"],
            //                 "status" => "failed",
            //                 "error" => isset($result['data']) ? $result['data'] : "Unknown error"
            //             ];
            //             $hasErrors = true;
            //         } else {
            //             // Mark as success
            //             $updateData["status"] = $qboid == 0 ? 1 : 2;
            //             $updateData["qboid"] = isset($result["data"]["CreditMemo"]["Id"]) ? $result["data"]["CreditMemo"]["Id"] : ($qboid ?: null);
            //             $results[] = [
            //                 "tranid" => $row["tranid"],
            //                 "status" => "success",
            //                 "qboid" => $updateData["qboid"]
            //             ];
            //         }

            //         //Always update DB
            //         $invoiceService->update($updateData, "wgcentralsupply");
            //         //return $response($credit, 200);
            //     } catch (Exception $e) {
            //         // Catch QBO errors / customer creation errors
            //         $updateData["status"] = 4;
            //         $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
            //         $invoiceService->update($updateData, "wgcentralsupply");

            //         $results[] = [
            //             "tranid" => $row["tranid"],
            //             "status" => "failed",
            //             "error" => $e->getMessage()
            //         ];
            //         $hasErrors = true;
            //     }
            // }

            // Return overall result
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
    public function delete_credit($request, $response, $params)
    {
        $invoiceService = new InvoicesService($this->db);

        try {
            $input = $request->validate([
                "data"               => "required|array|min:1",
                "token"              => "required",
                'data.*.tranid'      => 'required|int|min:1',
                'data.*.qboid'       => 'required',
            ]);

            $credits = $input["data"];
            $token = $input["token"];
            $results = [];
            $hasErrors = false;

            foreach ($credits as $row) {
                try {
                    $qbo = new QboEntityService($this->db, $this->companyId);
                    $synctoken = $qbo->synctoken($row["qboid"], $token, "CreditMemo");
                    $deleteResult = QBO::delete()->CreditMemo($row["qboid"], $synctoken['synctoken']);

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
    public function find_credit($request, $response, $params)
    {
        $token = $request["token"];
        $id = $request["id"];
        $qbo = new QboEntityService($this->db, $this->companyId);
        $details = $qbo->details($id, $token, "CreditMemo");
        return $response($details, $details['status']);
    }
    private function line_credit(array $tranids)
    {
        $invoiceService = new InvoicesService($this->db);
        $qbo            = new QboEntityService($this->db, $this->companyId);
        $discountItemId = $qbo->discount(); // called once

        // One query for all tranids
        $allDetails = $invoiceService->credit_line_batch($tranids);
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
    // private function line_credit($id)
    // {
    //     $invoiceService = new InvoicesService($this->db);
    //     $details = $invoiceService->credit_line($id);

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

    //         $qty = isset($list["qty"]) ? abs($list["qty"]) : 1;
    //         $price = (isset($list["vat"]) && $list["vat"] > 0)
    //             ? (isset($list["price"]) && $list["price"] ? abs($list["price"] / 1.12) : 0)
    //             : (isset($list["price"]) ? abs($list["price"]) : 0);
    //         $gross = isset($list["gross"]) ? abs($list["gross"]) : 0;
    //         $amount = (isset($list["vat"]) && $list["vat"] > 0)
    //             ? (isset($list["gross"]) && $list["gross"] ? abs($list["gross"] / 1.12) : 0)
    //             : (isset($list["gross"]) ? abs($list["gross"]) : 0);
    //         $itemid = $qbo->cm_to_salary($list["center"], $list["descriptions"], $list["itemid"]);
    //         $lines[] = [
    //             "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
    //             "DetailType" => "SalesItemLineDetail",
    //             "SalesItemLineDetail" => [
    //                 "TaxInclusiveAmt" => $gross,
    //                 "ItemRef" => ["value" => $itemid],
    //                 "TaxCodeRef" => [
    //                     "value" => (isset($list["vat"]) && $list["vat"] > 0) ? $qbo->vat("vat-s") : $qbo->vat("vat-ex")
    //                 ],
    //                 "Qty" => $qty,
    //                 "UnitPrice" => $price,
    //                 "DiscountAmt" => (isset($list["ldiscount"]) ? $list["ldiscount"] : 0) + (isset($list["discount"]) ? $list["discount"] : 0),
    //             ],
    //             "LineNum" => $index + 1,
    //             "Amount" => $amount,
    //         ];
    //         $index++;

    //         // Add to totals for subtotal and discounts
    //         $grossTotal += isset($list["gross"]) ? abs($list["gross"]) : 0;
    //         $discountTotal += isset($list["discount"]) ? $list["discount"] : 0;
    //         $ldiscountTotal += isset($list["ldiscount"]) ? $list["ldiscount"] : 0;
    //     }

    //     // Add subtotal line
    //     // $lines[] = [
    //     //     "LineNum" => count($details) + 1,
    //     //     "Description" => "Subtotal: PHP" . abs($grossTotal),
    //     //     "DetailType" => "DescriptionOnly",
    //     //     "DescriptionLineDetail" => ["TaxCodeRef" => ["value" => $qbo->vat("vat-ex")]],
    //     // ];

    //     // // Add discount line
    //     // $discountSum = $discountTotal + $ldiscountTotal;
    //     // $lines[] = [
    //     //     "Description" => "Line Discount & 20% Discount",
    //     //     "DetailType" => "SalesItemLineDetail",
    //     //     "SalesItemLineDetail" => [
    //     //         "TaxCodeRef" => ["value" => $qbo->discountvat()],
    //     //         "Qty" => abs(0),
    //     //         "UnitPrice" => abs(0 - $discountSum),
    //     //         "ItemRef" => ["value" => $qbo->discount(), "name" => "Discount"],
    //     //     ],
    //     //     "LineNum" => count($details) + 2,
    //     //     "Amount" => abs(0 - $discountSum),
    //     // ];

    //     return $lines;
    // }
}
