<?php

/**
 * Route requests to sample responses on PHP's local development server.
 *
 * Start from the repository root with php -S 127.0.0.1:8080 mock.php.
 */

declare(strict_types=1);

// Each route lists the HTTP status, response file and, for POST, the expected JSON.
$fixtures = [
    'POST /api/airtime.php' => [
        201,
        'airtime-queued.json',
        [
            'msisdn' => '0821234567',
            'network' => 'vodacom',
            'amount' => 10,
            'reference' => 'EXAMPLE-AIRTIME-001',
        ],
    ],
    'GET /api/airtime.php?request_id=1001' => [200, 'airtime-delivered.json'],

    'GET /api/electricity.php?meter_number=01234567890' => [200, 'electricity-meter.json'],
    'POST /api/electricity.php' => [
        201,
        'electricity-accepted.json',
        [
            'meter_number' => '01234567890',
            'amount' => 150,
            'client_reference' => 'EXAMPLE-ELEC-001',
            'recipient' => '0821234567',
            'send_sms' => false,
        ],
    ],
    // Both the returned reference and the original reference can identify this order.
    'GET /api/electricity.php?client_reference=EXAMPLE-ELEC-001_1783324800_000000000001' => [
        200,
        'electricity-completed.json',
    ],
    'GET /api/electricity.php?client_reference=EXAMPLE-ELEC-001' => [200, 'electricity-completed.json'],

    'POST /api/order.php' => [
        201,
        'allnet-placed.json',
        [
            'quantity' => 2,
            'value' => 10,
            'description' => 'Example reward order',
        ],
    ],
    'GET /api/fetch_order.php?order_id=2001' => [200, 'allnet-vouchers.json'],
];

header('Content-Type: application/json');

$sendError = static function (int $status, string $message): never {
    http_response_code($status);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_THROW_ON_ERROR);
    exit;
};

$route = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
if (!isset($fixtures[$route])) {
    $sendError(404, 'No sample response for this method and URL.');
}

// Check each provider's header using the dummy credentials shown in the README.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$allNet = in_array($path, ['/api/order.php', '/api/fetch_order.php'], true);
$header = $allNet ? 'HTTP_X_API_KEY' : 'HTTP_AUTHORIZATION';
$credential = $allNet ? 'sample-key' : 'Bearer sample-token';
if (($_SERVER[$header] ?? '') !== $credential) {
    $sendError(401, 'Use the sample credential from the README.');
}

$expected = $fixtures[$route][2] ?? null;
if ($expected !== null) {
    $contentType = explode(';', $_SERVER['CONTENT_TYPE'] ?? '', 2)[0];
    if (strtolower(trim($contentType)) !== 'application/json') {
        $sendError(415, 'Send the sample body as application/json.');
    }

    $body = file_get_contents('php://input');
    $request = $body === false ? null : json_decode($body, true);
    if (!is_array($request)) {
        $sendError(400, 'Send the sample JSON object from the README.');
    }

    // JSON object keys can arrive in any order. Their values must match the sample.
    ksort($expected);
    ksort($request);
    if ($request !== $expected) {
        $sendError(400, 'The JSON body does not match this sample.');
    }
}

[$status, $file] = $fixtures[$route];
$responseFile = __DIR__ . '/samples/' . $file;
if (!is_readable($responseFile)) {
    $sendError(500, 'The sample response file is missing or unreadable.');
}

http_response_code($status);
readfile($responseFile);
