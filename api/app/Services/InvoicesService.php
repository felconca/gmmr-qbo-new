<?php

namespace App\Services;

class InvoicesService
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Update QBO sync status for an invoice.
     *
     * @param array $data
     * @return int Number of affected rows
     */
    public function update(array $data, $db)
    {
        $tranid    = $data["tranid"];
        $amount    = $data["amount"];
        $status    = $data["status"];
        $qboid = isset($data["qboid"]) ? $data["qboid"] : null;
        $timestamp = date("Y-m-d H:i:s");

        $update = [
            "sent_to_qbo_amt"  => $amount,
            "sent_to_qbo"      => $status,
            "sent_to_qbo_date" => $timestamp,
        ];

        if (array_key_exists("qboid", $data)) {
            $update["sent_to_qbo_id"] = $qboid;
        }
        if ($status == 2) {
            $update["sent_to_qbo_update_amt"] = $amount;
        }

        return  $this->conn->$db
            ->update("possales", $update)
            ->WHERE(["TranRID" => $tranid]);
    }
    public function update_inventory(array $data, $db)
    {
        $tranid    = $data["tranid"];
        $amount    = $data["amount"];
        $status    = $data["status"];
        $qboid = isset($data["qboid"]) ? $data["qboid"] : null;
        $timestamp = date("Y-m-d H:i:s");

        $update = [
            "sent_to_qbo_cost_amt"  => $amount,
            "sent_to_cost_qbo"      => $status,
            "sent_to_qbo_cost_date" => $timestamp,
        ];

        if (array_key_exists("qboid", $data)) {
            $update["sent_to_qbo_cost_id"] = $qboid;
        }
        if ($status == 2) {
            $update["sent_to_qbo_cost_update_amt"] = $amount;
        }

        return  $this->conn->$db()
            ->update("possales", $update)
            ->WHERE(["TranRID" => $tranid]);
    }
    public function nonpharma_line($id)
    {
        $items = $this->conn->wgcentralsupply()
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
                pr.itemclass AS class,
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
            ->WHERE("pd.DisLineCanceled = 0")
            ->WHERE(["pd.TranRID" => $id])
            ->get();
        if ($items) {
            return $items;
        } else {
            return [];
        }
    }
    public function pharmacy_line($id)
    {
        $items = $this->conn->wgfinance()
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
                pr.itemclass AS class,
                ROUND(pd.UnitCost, 2) AS cost,
                pd.line_netofvat AS netofvat,
                pd.ExtendAmount AS netamount,
                cx.qbo_inv_id AS invid,
                cx.qbo_cost_id AS costid,
                cx.qbo_items_id AS itemid",
                "possales_details pd"
            )
            ->LEFTJOIN("product pr", "pr.ProductRID = pd.ProductRID")
            ->LEFTJOIN("ipadrbg.lkup_centers cx", "cx.centerRID = pd.centerRID")
            ->WHERE("pd.DisLineCanceled = 0")
            ->WHERE(["pd.TranRID" => $id])
            ->get();
        if ($items) {
            return $items;
        } else {
            return [];
        }
    }
    public function pf_line($id)
    {
        $items = $this->conn->wgcentralsupply()
            ->SELECT(
                "pd.TranRID AS tranid,
                pd.OrderDetailRID AS orderid,
                pd.ProductRID AS productid,
                pd.SalesTaxRID AS taxid,
                CONCAT_WS(', ', CONCAT_WS(' ', dx.LastName, NULLIF(dx.namesuffix, '')), CONCAT_WS(' ', dx.FirstName, NULLIF(dx.MiddleName, ''))) AS descriptions,
                pd.VatAmnt AS vat,
                pd.line_Discount AS ldiscount,
                pd.DiscountApplied AS discount,
                pd.SoldPrice AS price,
                pd.SoldQty AS qty,
                pd.GrossLine AS gross,
                ROUND(pd.UnitCost, 2) AS cost,
                pd.line_netofvat AS netofvat,
                pd.ExtendAmount AS netamount,
                cx.qbo_inv_id AS invid,
                cx.qbo_cost_id AS costid,
                cx.qbo_items_id AS itemid",
                "possales_details pd"
            )
            ->LEFTJOIN("ipadrbg.px_data dx", "dx.PxRID = pd.PFDoksPxRID")
            ->LEFTJOIN("ipadrbg.lkup_centers cx", "cx.centerRID = pd.centerRIDpbr")
            ->WHERE("pd.DisLineCanceled = 0")
            ->WHERE(["pd.TranRID" => $id])
            ->get();
        if ($items) {
            return $items;
        } else {
            return [];
        }
    }
}
