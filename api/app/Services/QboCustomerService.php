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
            $localResult = (array)$localResult;
            if ($localResult && isset($localResult['customerref']) && (int)$localResult['customerref'] > 0) {
                return (int)$localResult['customerref'];
            }
        }

        // 2. Not in local, try lookup by name in QBO
        $escapedName = str_replace("'", "\\'", $fullyQualifiedName);
        $existing = QBO::query("SELECT * FROM Customer WHERE FullyQualifiedName='" . $escapedName . "'");
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

    public function create(array $data, $token)
    {
        QBO::setAuth($this->companyId, $token);
        $patients = [];

        foreach ($data as $entry) {
            $pxid       = isset($entry['pxid'])   ? $entry['pxid']   : 0;
            $lastName   = isset($entry['lname'])  ? $entry['lname']  : '';
            $firstName  = isset($entry['fname'])  ? $entry['fname']  : '';
            $middleName = isset($entry['mname'])  ? $entry['mname']  : '';
            $suffix     = isset($entry['suffix']) ? $entry['suffix'] : '';

            $nameParts = [];
            if ($lastName !== '')   $nameParts[] = $lastName . ',';
            if ($firstName !== '')  $nameParts[] = $firstName;
            if ($middleName !== '') $nameParts[] = $middleName;
            if ($suffix !== '')     $nameParts[] = $suffix;
            $fullyQualifiedName = trim(implode(' ', $nameParts));

            // Gate 1: QBO lookup by name
            $escapedName = str_replace("'", "\\'", $fullyQualifiedName);
            $existing    = QBO::query(
                "SELECT * FROM Customer WHERE FullyQualifiedName='" . $escapedName . "'"
            );

            if (
                is_array($existing) &&
                isset($existing['status']) &&
                in_array($existing['status'], [200, 201], true) &&
                isset($existing['data']['QueryResponse']['Customer'][0])
            ) {
                $qboId = $existing['data']['QueryResponse']['Customer'][0]['Id'];
            } else {
                // Gate 2: Truly new — create in QBO
                $customerData = [
                    "GivenName"          => $firstName,
                    "FamilyName"         => $lastName,
                    "FullyQualifiedName" => $fullyQualifiedName,
                    "Notes"              => "Chart No. " . $pxid,
                    "CustomField"        => [[
                        "DefinitionId" => "3",
                        "Name"         => "PxRID",
                        "Type"         => "StringType",
                        "StringValue"  => $pxid
                    ]]
                ];
                if ($middleName !== '') $customerData['MiddleName'] = $middleName;
                if ($suffix !== '')     $customerData['Suffix']     = $suffix;

                $result = QBO::create()->Customer($customerData);

                if (
                    !is_array($result) ||
                    !isset($result['status']) ||
                    !in_array($result['status'], [200, 201], true) ||
                    !isset($result['data']['Customer']['Id'])
                ) {
                    throw new Exception("QBO Customer creation failed for pxid: " . $pxid);
                }

                $qboId = $result['data']['Customer']['Id'];
            }

            // Single update — runs regardless of which gate resolved $qboId
            $this->updatePatient($pxid, $qboId);
            $patients[$pxid] = $qboId;
        }

        return $patients;
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
    // No, this method signature is not valid in PHP 5.6.40.
    // PHP 5.6 supports type hints for arrays in parameters, but does not support return type declarations.
    // The following is valid in PHP 5.6.40:
    // public function batchCheckPatients(array $pxids)
    // {
    //     if (empty($pxids)) return [];

    //     $rows = $this->db
    //         ->ipadrbg()
    //         ->SELECT("PxRID, qbo_px_id AS customerref", "px_data")
    //         ->WHERE_IN("PxRID", $pxids)
    //         ->get();

    //     $map = [];
    //     foreach ($rows as $row) {
    //         $row = (array)$row;
    //         $map[$row['PxRID']] = (int)$row['customerref'];
    //     }
    //     return $map;
    // }

    // public function batchLookupByNames(array $pxidToName, string $token): array
    // {
    //     if (empty($pxidToName)) return [];

    //     QBO::setAuth($this->companyId, $token);

    //     $namesToPxid = array_flip($pxidToName); // name => pxid
    //     $escaped = array_map(
    //         fn($n) => "'" . str_replace("'", "\\'", $n) . "'",
    //         array_values($pxidToName)
    //     );

    //     // QBO supports querying by FullyQualifiedName with 'IN', but documentation is minimal.
    //     // Empirically, querying Customer by FullyQualifiedName with an IN clause works for a reasonable number of names.
    //     // Caveat: QBO API limits the length of queries (~1000 chars typical per docs).
    //     $whereClause = "WHERE FullyQualifiedName IN (" . implode(',', $escaped) . ")";
    //     $existing = QBO::query()->Customer($whereClause);

    //     $result = [];
    //     if (
    //         is_array($existing) &&
    //         isset($existing['data']['QueryResponse']['Customer'])
    //     ) {
    //         foreach ($existing['data']['QueryResponse']['Customer'] as $c) {
    //             $name = $c['FullyQualifiedName'];
    //             if (isset($namesToPxid[$name])) {
    //                 $pxid = $namesToPxid[$name];
    //                 $result[$pxid] = $c['Id'];
    //             }
    //         }
    //     }

    //     return $result;
    // }
}
