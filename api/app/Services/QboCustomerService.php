<?php

namespace App\Services;

use Exception;
use QuickBooksOnlineHelper\Facades\QBO;

class QboCustomerService
{
    protected $db;
    protected $companyId;

    public function __construct($db, $companyId)
    {
        $this->db = $db;
        $this->companyId = $companyId;
    }

    public function createCustomer(array $input)
    {
        $nameParts = array_filter([
            $input['fname'],
            isset($input['mname']) ? $input['mname'] : null,
            $input['lname'],
            isset($input['suffix']) ? $input['suffix'] : null,
        ]);

        $customer = [
            "GivenName"            => $input["fname"],
            "FamilyName"           => $input["lname"],
            "FullyQualifiedName"   => implode(' ', $nameParts),
            "Notes"                => "Chart No. {$input['pxid']}",
        ];

        if (!empty($input['mname'])) {
            $customer['MiddleName'] = $input['mname'];
        }

        if (!empty($input['suffix'])) {
            $customer['Suffix'] = $input['suffix'];
        }

        QBO::setAuth($this->companyId, $input['token']);
        $result = QBO::create()->Customer($customer);

        if (!in_array($result['status'], [200, 201], true)) {
            throw new Exception("QBO Customer creation failed");
        }

        $customerId = $result['data']['Customer']['Id'];

        if (!empty($input['pxid'])) {
            $this->updatePatient($input['pxid'], $customerId);
        }

        return $customerId;
    }

    private function updatePatient($pxid, $qboid)
    {
        return $this->db->ipadrbg()
            ->update("px_data", ["qbo_px_id" => $qboid])
            ->WHERE(["PxRID" => $pxid]);
    }
}
