<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class AdvancesToController extends Rest
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
    }

    public function employee($request, $response, $params)
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

            $advances = $this->db->wgcentralsupply()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo AS sent_status",
                    "p.sent_to_qbo_id AS sent_id",
                    "p.sent_to_qbo_date AS sent_date",
                    "p.sent_to_qbo_amt AS booked_amt",
                    "p.sent_to_qbo_amt AS updated_amt",
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
                    "CONCAT(cxto.FirstName, ' ', IFNULL(SUBSTRING(cxto.MiddleName,1,1),''), ' ', cxto.LastName, ' ', IFNULL(cxto.namesuffix, '')) AS employee",

                    "ltf.TranStatusDescription AS transtatus"
                ], "possales_details pd")
                ->LEFTJOIN("possales p", "p.TranRID = pd.TranRID")
                ->LEFTJOIN("credit_memo cm", "cm.TranRID = p.TranRID")
                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("ipadrbg.px_data cxto", "cxto.PxRID = cm.creditto")
                ->LEFTJOIN("lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("(p.pinnedby > 0 OR p.bookedbycashier > 0)")
                ->WHERE("p.ApprovedBy = 0")
                ->WHERE("p.NetAmountDue != 0")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE("p.TranStatus = 22")
                ->WHERE("cm.creditto > 0")
                ->WHERE("cm.Deleted = 0")
                ->WHERE_NOT_IN(
                    "cxto.PersonDataType",
                    ['PATIENT', 'Patient', 'HMO', 'Corporate Acct', 'Health Facility', 'Assistance']
                )
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($isbooked != -1) {
                $advances->WHERE(["p.sent_to_qbo" => $isbooked]);
            }

            $rows = $advances->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (\Throwable $e) {
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
    public function book_employee_credit($request, $response)
    {
        try {
            $qboService = new QboCustomerService($this->db, $this->companyId);
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

            foreach ($credits as $row) {
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

                    $isUpdate = $qboid > 0;
                    $action = $isUpdate ? QBO::update() : QBO::create();

                    $line = $this->line_credit($row["tranid"]);

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
                        // FIX: set 'Id' to QBO credit id (not to $qbo service), 'sparse' must be true, 'SyncToken' is required
                        $credit['Id'] = $qboid; // NOT $qbo (service), should be the QBO credit id
                        $credit['sparse'] = true;
                        $synctoken = $qbo->synctoken($row["qboid"], $token, "CreditMemo");

                        if ($synctoken) {
                            $credit["SyncToken"] = $synctoken['synctoken'];
                        } else {
                            // Protect, must have SyncToken for update
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                    }

                    $result = $action->CreditMemo($credit);

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
                        $updateData["qboid"] = isset($result["data"]["CreditMemo"]["Id"]) ? $result["data"]["CreditMemo"]["Id"] : ($qboid ?: null);
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success",
                            "qboid" => $updateData["qboid"]
                        ];
                    }

                    //Always update DB
                    $invoiceService->update($updateData, "wgcentralsupply");
                    //return $response($credit, 200);
                } catch (Exception $e) {
                    // Catch QBO errors / customer creation errors
                    $updateData["status"] = 4;
                    $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
                    $invoiceService->update($updateData, "wgcentralsupply");

                    $results[] = [
                        "tranid" => $row["tranid"],
                        "status" => "failed",
                        "error" => $e->getMessage()
                    ];
                    $hasErrors = true;
                }
            }

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
    public function book_employee_debit($request)
    {
        try {
            $qboService = new QboCustomerService($this->db, $this->companyId);
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

            $invoices = $input["data"];
            $token = $input["token"];
            $hasErrors = false;
            $results = [];

            foreach ($invoices as $row) {
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

                    $isUpdate = $qboid > 0;
                    $action = $isUpdate ? QBO::update() : QBO::create();

                    $line = $this->line_invoice($row["tranid"]);

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

                    $invoice = [
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
                        // FIX: set 'Id' to QBO Invoice id (not to $qbo service), 'sparse' must be true, 'SyncToken' is required
                        $invoice['Id'] = $qboid; // NOT $qbo (service), should be the QBO invoice id
                        $invoice['sparse'] = true;
                        $synctoken = $qbo->synctoken($row["qboid"], $token, "Invoice");

                        if ($synctoken) {
                            $invoice["SyncToken"] = $synctoken['synctoken'];
                        } else {
                            // Protect, must have SyncToken for update
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                    }

                    $result = $action->Invoice($invoice);

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
                        $updateData["qboid"] = isset($result["data"]["Invoice"]["Id"]) ? $result["data"]["Invoice"]["Id"] : ($qboid ?: null);
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success",
                            "qboid" => $updateData["qboid"]
                        ];
                    }

                    //Always update DB
                    $invoiceService->update($updateData, "wgcentralsupply");
                    //return $response($invoice, 200);
                } catch (Exception $e) {
                    // Catch QBO errors / customer creation errors
                    $updateData["status"] = 4;
                    $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
                    $invoiceService->update($updateData, "wgcentralsupply");

                    $results[] = [
                        "tranid" => $row["tranid"],
                        "status" => "failed",
                        "error" => $e->getMessage()
                    ];
                    $hasErrors = true;
                }
            }

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

    public function debit_employee($refno) {}
}
