<?php

namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;
use Error;

class NonPharmaController extends Rest
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
        try {
            $input = $request->validate([
                "start_dt" => "required|date",
                "end_dt" => "required|date",
                "isbooked" => "required|int:min:1",
                "status" => "required|number:min:1",
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
                    "p.sent_to_qbo AS sent_status",
                    "SUM(pd.line_Discount) AS ldiscount",
                    "SUM(pd.DiscountApplied) AS discount",
                    "SUM(pd.line_netofvat) AS netofvat",
                    "SUM(pd.VatAmnt) AS vat",
                    "SUM(pd.GrossLine) AS gross",
                    "SUM(pd.ExtendAmount) AS netamount",
                    "p.TranStatus AS tstatus",

                    "px.LastName AS pxlname",
                    "px.MiddleName AS pxmname",
                    "px.FirstName AS pxfname",
                    "px.namesuffix AS suffix",

                    "CONCAT(px.LastName, ', ', px.FirstName) AS lnamefirst",
                    "CONCAT(px.FirstName, ', ', px.LastName) AS fnamefirst",
                    "CONCAT(px.LastName, ', ', px.FirstName, px.namesuffix) AS lnamefirstsx",
                    "CONCAT(px.FirstName, ', ', px.LastName, px.namesuffix) AS fnamefirstsx",
                    "CONCAT(px.FirstName, ' ', IFNULL(SUBSTRING(px.MiddleName,1,1),''), ' ', px.LastName, ' ', px.namesuffix) AS completepx",

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
                ->WHERE_NOT_IN("p.PxRID", [1993, 1999, 14336])
                ->WHERE_BETWEEN("p.TranDate", $start_dt, $end_dt);

            if ($status > 0) {
                $invoices->WHERE(["p.TranStatus" => $status]);
            } else {
                $invoices->WHERE_IN("p.TranStatus", [1, 4, 5, 6, 16, 21]);
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
                $details = $this->details($request, $response, $params);
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
            ]);
            $tranid = $input["tranid"];
            $qboid = $input["qboid"];
            $amount = $input["amount"];
            $timestamp = date("Y-m-d H:i:s");

            $invoice = $this->db->wgcentralsupply()
                ->update('possales', [
                    "sent_to_qbo_amt" => $amount,
                    "sent_to_qbo" => 1,
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
    private function details($request, $response, $params)
    {
        try {
            $input = $request->validate([
                "id" => "required",
            ]);
            $items = $this->db->wgcentralsupply()
                ->SELECT(
                    "pd.TranRID AS tranid,
                    pd.OrderDetailRID AS orderid,
                    pd.ProductRID AS productid,
                    pd.SalesTaxRID AS taxid,
                    pr.`Description` AS descriptions,
                    pd.VatAmnt AS vat,
                    pd.line_Discount AS ldiscount,
                    pd.DiscountApplied AS discount,
                    pd.SoldPrice AS price,
                    pd.SoldQty AS qty,
                    pd.GrossLine AS gross,
                    pr.DeptCode AS codes,
                    ROUND(pd.UnitCost, 2) AS cost,
                    pd.line_netofvat AS netofvat,
                    pd.ExtendAmount AS netamount,
                    cx.qbo_inv_id AS invid,
                    cx.qbo_cost_id AS costid,
                    cx.qbo_items_id AS itemid",
                    "possales_details pd"
                )
                ->LEFTJOIN("product pr", "pr.ProductRID = pd.ProductRID")
                ->LEFTJOIN("ipadrbg.lkup_centers cx", "cx.centerRID = pd.centerRIDpbr")
                ->WHERE(["pd.TranRID" => $input['id']])->get();
            if ($items) {
                return $items;
            } else {
                return [];
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
