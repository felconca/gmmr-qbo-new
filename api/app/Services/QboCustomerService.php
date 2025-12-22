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
        // Prepare input values using PHP 5.6 compatible ternary
        $lastName   = isset($input['lname']) ? $input['lname'] : '';
        $firstName  = isset($input['fname']) ? $input['fname'] : '';
        $middleName = isset($input['mname']) ? $input['mname'] : '';
        $suffix     = isset($input['suffix']) ? $input['suffix'] : '';
        $pxid       = isset($input['pxid']) ? $input['pxid'] : null;
        $token      = isset($input['token']) ? $input['token'] : null;

        // Compose FullyQualifiedName as "Lastname, Firstname Middlename Suffix"
        $qualifiedParts = array();
        if ($firstName !== '')   $qualifiedParts[] = $firstName;
        if ($middleName !== '')  $qualifiedParts[] = $middleName;
        if ($suffix !== '')      $qualifiedParts[] = $suffix;

        $fullyQualifiedName = rtrim($lastName . ', ' . implode(' ', $qualifiedParts));

        QBO::setAuth($this->companyId, $token);

        // 1. Try to get local patient record's QBO id
        $customerId = 0;
        $localResult = null;
        if (!empty($pxid)) {
            $localResult = $this->checkPatient($pxid);
            if ($localResult && isset($localResult['customerref']) && (int)$localResult['customerref'] > 0) {
                return (int)$localResult['customerref'];
            }
        }

        // 2. Not in local, try lookup by name in QBO
        $escapedName = str_replace("'", "\\'", $fullyQualifiedName);
        $existing = QBO::query()->Customer("WHERE FullyQualifiedName='" . $escapedName . "'");
        if (
            is_array($existing) &&
            isset($existing['status']) &&
            in_array($existing['status'], array(200, 201), true) &&
            isset($existing['data']['QueryResponse']['Customer']) &&
            is_array($existing['data']['QueryResponse']['Customer']) &&
            count($existing['data']['QueryResponse']['Customer']) > 0
        ) {
            $customerFromQBO = $existing['data']['QueryResponse']['Customer'][0];
            if ($customerFromQBO) {
                // Update our patient record if needed
                if ($localResult && (int)$localResult['customerref'] === 0 && !empty($pxid)) {
                    $this->updatePatient($pxid, $customerFromQBO['Id']);
                }
                return $customerFromQBO['Id'];
            }
        }

        // 3. Not found, create new customer record on QBO
        $customerData = array(
            "GivenName"          => $firstName,
            "FamilyName"         => $lastName,
            "FullyQualifiedName" => $fullyQualifiedName,
            "Notes"              => "Chart No. " . $pxid,
            "CustomField"        => array(
                array(
                    "DefinitionId" => "3",
                    "Name"         => "PxRID",
                    "Type"         => "StringType",
                    "StringValue"  => $pxid
                )
            )
        );
        if ($middleName !== '')   $customerData['MiddleName'] = $middleName;
        if ($suffix !== '')       $customerData['Suffix'] = $suffix;

        $result = QBO::create()->Customer($customerData);

        if (
            !is_array($result) ||
            !isset($result['status']) ||
            !in_array($result['status'], array(200, 201), true) ||
            !isset($result['data']['Customer']['Id'])
        ) {
            throw new Exception("QBO Customer creation failed");
        }

        $customerId = $result['data']['Customer']['Id'];

        if (!empty($pxid)) {
            $this->updatePatient($pxid, $customerId);
        }

        return $customerId;
    }

    private function updatePatient($pxid, $qboid)
    {
        return $this->db->ipadrbg()
            ->update("px_data", ["qbo_px_id" => $qboid])
            ->WHERE(["PxRID" => $pxid]);
    }
    private function checkPatient($pxid)
    {
        return $this->db
            ->ipadrbg()
            ->SELECT("qbo_px_id AS customerref", "px_data")
            ->WHERE(["PxRID" => $pxid])->first();
    }
}
