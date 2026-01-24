<?php

namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;

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
                ->WHERE("p.ApprovedBy = 0")
                ->WHERE("p.NetAmountDue != 0")
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

            $rows = $invoices->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
}
