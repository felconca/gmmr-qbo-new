<?php

namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;

class GMMRInvoicesController extends Rest
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
        return $response(['message' => 'GMMRInvoicesController index'], 200);
    }
    public function nonpharmacy($request, $response, $params)
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

            $builder = $this->db->wgcentralsupply()
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
                $builder->WHERE(["p.TranStatus" => $status]);
            } else {
                $builder->WHERE_IN("p.TranStatus", [1, 4, 5, 6, 16, 21]);
            }

            if ($isbooked != -1) {
                $builder->WHERE(["p.sent_to_qbo" => $isbooked]);
            }

            $rows = $builder->GROUPBY("p.TranRID")->ORDERBY("p.TranRID")->get();

            return $response($rows, 200);
        } catch (Exception $e) {
            return $response(["status" => 400, "error" => $e->getMessage()], 400);
        }
    }
}
