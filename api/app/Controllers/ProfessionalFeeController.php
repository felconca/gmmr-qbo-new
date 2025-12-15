<?php
namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;

class ProfessionalFeeController extends Rest
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
        return $response(['message' => 'ProfessionalFeeController index'], 200);
    }
}