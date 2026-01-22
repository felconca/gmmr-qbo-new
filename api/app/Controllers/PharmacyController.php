<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class PharmacyController extends Rest
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
                ->LEFTJOIN("wgcentralsupply.lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE("(p.pinnedby >= 0 OR p.bookedbycashier > 0)")
                ->WHERE("p.NetAmountDue != 0")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($status > 0) {
                $invoices->WHERE(["p.TranStatus" => $status]);
            } else {
                $invoices->WHERE_IN("p.TranStatus", [9, 10, 12]);
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
            $invoice = $this->db->wgfinance()
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
                    "IFNULL(px.namesuffix, '') AS suffix",

                    "ux.FirstName AS ufname",
                    "ux.LastName AS ulname",
                    "ltf.TranStatusDescription AS transtatus"
                ], "possales p")

                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("wgcentralsupply.lkup_transtatus_f ltf", "ltf.TranStatusF = p.TranStatus")
                ->WHERE(["p.TranRID" => $input['id']])->first();
            if ($invoice) {
                $details = $invoiceService->pharmacy_line($input["id"]);
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
    public function update($request, $response, $params)
    {
        try {
            $input = $request->validate([
                "tranid" => "required",
                "qboid" => "required",
                "amount" => "required|double|min:1",
                "status" => "required",
            ]);
            $tranid = $input["tranid"];
            $qboid = $input["qboid"];
            $amount = $input["amount"];
            $status = $input["status"];
            $timestamp = date("Y-m-d H:i:s");

            $invoice = $this->db->wgfinance()
                ->update('possales', [
                    "sent_to_qbo_amt" => $amount,
                    "sent_to_qbo" => $status,
                    "sent_to_qbo_id" => $qboid,
                    "sent_to_qbo_date" => $timestamp
                ])->WHERE(["TranRID" => $tranid]);

            if ($invoice) {
                return $response("Updated record $invoice", 200);
            } else {
                return $response("Cannot update invoice $invoice", 400);
            }
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }

    // qbo functions
    public function book_invoice($request, $response, $params)
    {
        try {
            $qboService = new QboCustomerService($this->db, $this->companyId);
            $invoiceService = new InvoicesService($this->db);

            $input = $request->validate([
                "data"             => "required|array|min:1",
                "token"            => "required",
                'data.*.tranid'    => 'required|int|min:1',
                'data.*.docnumber' => 'required|string',
                'data.*.txndate'   => 'required|date',
                'data.*.amount'    => 'required|float',
                'data.*.gtaxcalc'  => 'required|string',
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

                    // Always update DB
                    $invoiceService->update($updateData, "wgfinance");
                } catch (Exception $e) {
                    // Catch QBO errors / customer creation errors
                    $updateData["status"] = 4;
                    $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
                    $invoiceService->update($updateData, "wgfinance");

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

                    $invoiceService->update($updateData, "wgfinance");
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
    private function line_invoice($id)
    {
        $invoiceService = new InvoicesService($this->db);
        $details = $invoiceService->pharmacy_line($id);

        $lines = [];
        $qbo = new QboEntityService($this->db, $this->companyId);
        $index = 0;

        $grossTotal = 0;
        $discountTotal = 0;
        $ldiscountTotal = 0;

        foreach ($details as $list) {
            // Ensure $list is an array, not an object (stdClass)
            if (is_object($list)) {
                $list = (array)$list;
            }

            $itemRef = $qbo->pharmacy(
                isset($list['class']) ? $list['class'] : 0,
                isset($list['vat']) ? $list['vat'] : 0,
                isset($list["itemid"]) ? $list["itemid"] : 0
            );
            $lines[] = [
                "Description" => isset($list["descriptions"]) ? $list["descriptions"] : '',
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "TaxInclusiveAmt" => isset($list["gross"]) ? $list["gross"] : 0,
                    "ItemRef" => ["value" => $itemRef],
                    "TaxCodeRef" => [
                        "value" => (isset($list["vat"]) && $list["vat"] > 0) ? $qbo->vat("vat-s") : $qbo->vat("vat-ex")
                    ],
                    "Qty" => isset($list["qty"]) ? $list["qty"] : 1,
                    "UnitPrice" => (isset($list["vat"]) && $list["vat"] > 0)
                        ? (isset($list["price"]) && $list["price"] ? ($list["price"] / 1.12) : 0)
                        : (isset($list["price"]) ? $list["price"] : 0),
                    "DiscountAmt" => (isset($list["ldiscount"]) ? $list["ldiscount"] : 0) + (isset($list["discount"]) ? $list["discount"] : 0),
                ],
                "LineNum" => $index + 1,
                "Amount" => (isset($list["vat"]) && $list["vat"] > 0)
                    ? (isset($list["gross"]) && $list["gross"] ? ($list["gross"] / 1.12) : 0)
                    : (isset($list["gross"]) ? $list["gross"] : 0),
            ];
            $index++;

            // Add to totals for subtotal and discounts
            $grossTotal += isset($list["gross"]) ? $list["gross"] : 0;
            $discountTotal += isset($list["discount"]) ? $list["discount"] : 0;
            $ldiscountTotal += isset($list["ldiscount"]) ? $list["ldiscount"] : 0;
        }

        // Add subtotal line
        $lines[] = [
            "LineNum" => count($details) + 1,
            "Description" => "Subtotal: PHP{$grossTotal}",
            "DetailType" => "DescriptionOnly",
            "DescriptionLineDetail" => ["TaxCodeRef" => ["value" => $qbo->vat("vat-ex")]],
        ];

        // Add discount line
        $discountSum = $discountTotal + $ldiscountTotal;
        $lines[] = [
            "Description" => "Line Discount & 20% Discount",
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => [
                "TaxCodeRef" => ["value" => $qbo->discountvat()],
                "Qty" => 0,
                "UnitPrice" => 0 - $discountSum,
                "ItemRef" => ["value" => $qbo->discount(), "name" => "Discount"],
            ],
            "LineNum" => count($details) + 2,
            "Amount" => 0 - $discountSum,
        ];
        // if ($discountSum != 0) {

        // }

        return $lines;
    }
}
