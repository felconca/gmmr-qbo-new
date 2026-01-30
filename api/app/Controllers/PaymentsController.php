<?php

namespace App\Controllers;

use App\Services\InvoicesService;
use App\Services\QboCustomerService;
use App\Services\QboEntityService;
use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;

class PaymentsController extends Rest
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
        return $response(['message' => 'PaymentsController index'], 200);
    }
    public function walkin_payments($request, $response)
    {
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|numeric:min:1",
                "type" => "required|numeric:min:1",
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];
            $type = $input["type"];

            $invoices = $this->db->wgfinance()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo AS sent_link",
                    "p.sent_to_qbo_id AS sent_link_id",
                    "p.sent_to_qbo_pay AS sent_status",
                    "p.sent_to_qbo_pay_id AS sent_id",
                    "p.sent_to_qbo_pay_date AS sent_date",
                    "p.sent_to_qbo_pay_amt AS booked_amt",
                    "p.sent_to_qbo_pay_update_amt AS updated_amt",
                    "p.TranStatus AS tstatus",
                    "IFNULL(p.Remarks, '') AS remarks",
                    "IFNULL(p.Payment_for,'') AS payfor",

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

                    "IFNULL(py.RefNumber, p.RefNo) AS refnum",
                    "py.PayTypeRID AS ptypeid",
                    "pytype.PayType AS paytype",
                    "py.Tendered AS tendered",
                    "py.AmountDue AS netamount",

                ], "possales p")

                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("payment py", "py.TranRID = p.TranRID")
                ->LEFTJOIN("lookuppaytype pytype", "pytype.PayTypeRID = py.PayTypeRID")

                ->WHERE("(p.pinnedby >= 0 OR p.bookedbycashier > 0)")
                ->WHERE(["p.TranStatus" => 9])
                ->WHERE(["py.cancelled" => 0])
                ->WHERE("p.NetAmountDue != 0")
                ->WHERE_IN("p.sent_to_qbo", [1, 2])
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($type != -1) {
                $invoices->WHERE(["py.PayTypeRID" => $type]);
            }
            if ($isbooked != -1) {
                $invoices->WHERE(["p.sent_to_qbo_pay" => $isbooked]);
            }

            $rows = $invoices->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
    public function inpatient_payments($request, $response)
    {
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|numeric:min:1",
                "type" => "required|numeric:min:1",
            ]);
            $start_dt = $input['start_dt'];
            $end_dt = $input['end_dt'];
            $isbooked = $input["isbooked"];
            $type = $input["type"];

            $invoices = $this->db->wgcentralsupply()
                ->SELECT([
                    'p.TranRID AS tranid',
                    'p.PxRID AS pxid',
                    "p.TranDate AS trandate",
                    "p.sent_to_qbo AS sent_link",
                    "p.sent_to_qbo_pay AS sent_status",
                    "p.sent_to_qbo_pay_id AS sent_id",
                    "p.sent_to_qbo_pay_date AS sent_date",
                    "p.sent_to_qbo_pay_amt AS booked_amt",
                    "p.sent_to_qbo_pay_update_amt AS updated_amt",
                    "p.TranStatus AS tstatus",
                    "IFNULL(p.Remarks, '') AS remarks",
                    "IFNULL(p.Payment_for,'') AS payfor",

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

                    "IFNULL(py.RefNumber, p.RefNo) AS refnum",
                    "py.PayTypeRID AS ptypeid",
                    "pytype.PayType AS paytype",

                ], "possales_details pd")

                ->LEFTJOIN("possales p", "p.TranRID = pd.TranRID")
                ->LEFTJOIN("ipadrbg.px_data px", "px.PxRID = p.PxRID")
                ->LEFTJOIN("ipadrbg.px_data ux", "ux.PxRID = p.UserRID")
                ->LEFTJOIN("payment py", "py.TranRID = p.TranRID")
                ->LEFTJOIN("lookuppaytype pytype", "pytype.PayTypeRID = py.PayTypeRID")

                ->WHERE("(p.pinnedby > 0 OR p.bookedbycashier > 0)")
                ->WHERE("pd.DisLineCanceled = 0")
                ->WHERE(["p.TranStatus" => 14])
                ->WHERE(["py.cancelled" => 0])
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($type != -1) {
                $invoices->WHERE(["py.PayTypeRID" => $type]);
            }
            if ($isbooked != -1) {
                $invoices->WHERE(["p.sent_to_qbo_pay" => $isbooked]);
            }

            $rows = $invoices->GROUPBY("pd.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
    public function book_inpatient($request, $response)
    {
        try {
            $qboService = new QboCustomerService($this->db, $this->companyId);
            $invoiceService = new InvoicesService($this->db);

            $input = $request->validate([
                "data"             => "required|array|min:1",
                "token"            => "required",
                "data.*.amount"      => "required|float",
                "data.*.customerref" => "numeric",
                "data.*.depositref"  => "required",
                "data.*.fname"       => "required|string",
                "data.*.gstatus"     => "string",
                "data.*.lname"       => "required|string",
                "data.*.memo"        => "string",
                "data.*.methodref"   => "required|int|min:1",
                "data.*.mname"       => "string",
                "data.*.pxid"        => "required|int|min:1",
                "data.*.qboid"       => "numeric",
                "data.*.qbostatus"   => "numeric",
                "data.*.refnum"      => "required|string",
                "data.*.suffix"      => "string",
                "data.*.tranid"      => "required|int|min:1",
                "data.*.txndate"     => "required|date",
            ]);

            $payments = $input["data"];
            $token = $input["token"];
            $hasErrors = false;
            $results = [];

            foreach ($payments as $row) {
                QBO::setAuth($this->companyId, $token);
                $updateData = [
                    "tranid" => $row["tranid"],
                    "amount" => $row["amount"],
                    "qboid"  => 0,
                ];

                try {
                    $qbo = new QboEntityService($this->db, $this->companyId);
                    $qboid = isset($row['qboid']) ? $row['qboid'] : 0;
                    $isUpdate = $qboid > 0;
                    $action = $isUpdate ? QBO::update() : QBO::create();

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

                    $payment = [
                        "TxnDate" => $row['txndate'],
                        "TotalAmt" => $row['amount'],
                        "PaymentRefNum" => $row['refnum'],
                        "PaymentMethodRef" => ["value" => $row["methodref"]],
                        "DepositToAccountRef" => ["value" => $row["depositref"]],
                        "CustomerRef" => ["value" => $customer],
                        "PrivateNote" => $row['memo'],
                        "Line" => []
                    ];

                    if ($isUpdate) {
                        $payment['Id'] = $qboid;
                        $payment['sparse'] = true;
                        $synctoken = $qbo->synctoken($row["qboid"], $token, "Payment");

                        if ($synctoken) {
                            $payment["SyncToken"] = $synctoken['synctoken'];
                        } else {
                            throw new \Exception("SyncToken missing for QBO update");
                        }
                    }

                    $result = $action->Payment($payment);

                    if (!is_array($result) || !isset($result['status']) || !in_array($result['status'], [200, 201], true)) {
                        $updateData["status"] = 4;
                        $updateData["qboid"] = $isUpdate ? $qboid : 0;
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "failed",
                            "error" => isset($result['data']) ? $result['data'] : "Unknown error"
                        ];
                        $hasErrors = true;
                    } else {
                        $updateData["status"] = $qboid == 0 ? 1 : 2;
                        $updateData["qboid"] = isset($result["data"]["Payment"]["Id"]) ? $result["data"]["Payment"]["Id"] : ($qboid ?: null);
                        $results[] = [
                            "tranid" => $row["tranid"],
                            "status" => "success",
                            "qboid" => $updateData["qboid"]
                        ];
                    }

                    $invoiceService->update_pay_inpatient($updateData, "wgcentralsupply");
                } catch (Exception $e) {
                    $updateData["status"] = 4;
                    $updateData["qboid"] = isset($qboid) && $qboid > 0 ? $qboid : 0;
                    $invoiceService->update_pay_inpatient($updateData, "wgcentralsupply");

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
    public function book_walkin($request, $response) {}
    public function unbook_payments($request, $response, $params)
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
                    $synctoken = $qbo->synctoken($row["qboid"], $token, "Payment");
                    $deleteResult = QBO::delete()->Payment($row["qboid"], $synctoken['synctoken']);

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

                    if ($db === "wgfinance") {
                        $invoiceService->update_pay_walkin($updateData, $db);
                    } else {
                        $invoiceService->update_pay_inpatient($updateData, $db);
                    }
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
}
