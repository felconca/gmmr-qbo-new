<?php

namespace App\Controllers;

use Includes\Rest;
use Core\Database\Database;
use QuickBooksOnlineHelper\Facades\QBO;
use Redis\RedisCache;

class QBOServiceController extends Rest
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
        $this->redis = new RedisCache([
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password' => null,   // optional, leave null if no password
            'database' => 0,      // optional, default DB
            'timeout'  => 2,      // optional timeout in seconds
            'json'     => true,   // optional: automatically serialize/deserialize arrays
        ]);
    }

    public function index($request, $response, $params)
    {
        return $response(['message' => 'QBOTokenController index'], 200);
    }
    public function generate($request, $response, $params)
    {
        try {
            $input = $request->validate(["token" => "required|string"]);
            $refreshToken = $input["token"];

            $url = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer";
            $headers = [
                "Content-Type: application/x-www-form-urlencoded",
            ];

            // Setup POST fields
            $postFields = http_build_query([
                "grant_type" => "refresh_token",
                "refresh_token" => $refreshToken,
                'client_id' => $_ENV['QBO_CLIENTID'],
                'client_secret' => $_ENV['QBO_SECRETID'],
            ]);

            // Initialize cURL session
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

            // Execute cURL request
            $data = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Check for HTTP error code and response content
            if ($httpcode != 200) {
                $error = ["status" => $httpcode, "data" => $data];
                return $response($error, $httpcode);
                return;
            }

            $responseData = json_decode($data, true);
            // Return response and HTTP code
            $token = ["status" => $httpcode, "data" => [
                "refresh_token" => $responseData['refresh_token'],
                "access_token" => $responseData['access_token']
            ]];
            // set access token
            $this->redis->set(
                "accessToken",
                $responseData["access_token"],
                'EX',
                $responseData["expires_in"]
            );
            // set refresh_token
            $this->redis->set(
                "refreshToken",
                $responseData["refresh_token"],
                'EX',
                $responseData["x_refresh_token_expires_in"]
            );

            return $response($token, $httpcode);
        } catch (Exception $e) {
            return $response(["status" => 500, "error" => $e->getMessage()], 500);
        }
    }
    public function token($request, $response, $params)
    {
        try {
            // If Redis is not connected or throws a connection exception
            try {
                $accessToken = $this->redis->get('accessToken');
                $refreshToken = $this->redis->get('refreshToken');
            } catch (\RedisException $e) {
                return $response([
                    "status" => 503,
                    "error" => "No connection found to Redis server",
                    "details" => $e->getMessage()
                ], 503);
            } catch (\Throwable $e) {
                return $response([
                    "status" => 503,
                    "error" => "No connection found to Redis server (unexpected error)",
                    "details" => $e->getMessage()
                ], 503);
            }

            if ($accessToken !== null && $refreshToken !== null) {
                return $response([
                    "accesstoken" => $accessToken,
                    "refreshtoken" => $refreshToken
                ], 200);
            } else {
                return $response([
                    "status" => 400,
                    "error" => "One or both tokens are missing",
                    "details" => [
                        "accessToken" => $accessToken,
                        "refreshToken" => $refreshToken,
                    ]
                ], 400);
            }
        } catch (Exception $e) {
            return $response([
                "status" => 500,
                "error" => $e->getMessage()
            ], 500);
        }
    }
    public function create_customer($request, $response, $params)
    {
        $input = $request->validate([
            "token" => "required",
            "pxid"  => "required|int|min:1",
            "fname" => "required|string",
            "lname" => "required|string",
        ]);

        $service = new QboCustomerService($this->db, $this->companyId);
        $customerId = $service->createCustomer($input);

        return $response([
            "status" => 200,
            "customer_id" => $customerId
        ], 200);
    }
    // get list of items
    public function items_list($request, $reponse)
    {
        try {
            $input = $request->validate([
                "token" => "required",
            ]);
            $token = $input["token"]; // accestoken

            QBO::setAuth($this->companyId, $token);
            $items = QBO::all()->Item();

            return $reponse([
                'status' => 200,
                'items' => $items['data']['QueryResponse']['Item']
            ], 200);
        } catch (\Exception $e) {
            return $reponse([
                'status' => 500,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
