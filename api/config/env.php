<?php
return array(
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASSWORD' => '',
    'DB_CONNECTIONS' => 'ipadrbg,wgcentralsupply,wgfinance',
    'DB_CHARSET' => 'utf8mb4',
    'DB_COLLATION' => 'utf8mb4_general_ci',

    // SESSIONS VARIABLE
    'AUTH_SESSION_NAME' => 'GMMRQBO_SESSION',
    'AUTH_SESSION_LIFETIME' => 0,
    'AUTH_SESSION_HTTPONLY' => true,
    'AUTH_SESSION_SECURE' => true,
    'AUTH_SESSION_SAMESITE' => 'STRICT',

    // JWT SECRET
    'JWT_SECRET' => 'your-super-secret-key',

    'REDIS_HOST' => 'localhost',
    'REDIS_PORT' => 6379,
    'REDIS_USERNAME' => '',
    'REDIS_PASSWORD' => '',

    'QBO_CLIENTID' => 'AB5Qn4rOn8afFusuKlbm0nKjeiPcSeYuubhumfyNjxgwBjK7Pu',
    'QBO_SECRETID' => 'c8p3wVZ0H8NA85Ul1bJsESLoimYBVN2A2D1yp4kv',
    'QBO_COMPANYID' => '9341452518171040',
    'QBO_AUTH_SCOPE' => 'com.intuit.quickbooks.accounting openid profile email phone address',
    'QBO_REALM_ID' => '9341452518171040',
    'QBO_BASE_URL' => 'production',
    'QBO_AUTHORIZATION_URL' => 'https://appcenter.intuit.com/connect/oauth2',
    'QBO_TOKEN_ENDPOINT_URL' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
    'QBO_REDIRECT_URI' => 'http://localhost/api/quickbooks/callback',
);
