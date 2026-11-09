<?php

declare(strict_types=1);

require __DIR__ . '/http.php';

$url = 'http://127.0.0.1:8080/api/airtime.php';
$auth = 'Authorization: Bearer sample-token';

try {
    $queued = requestJson($url, $auth, [
        'msisdn' => '0821234567',
        'network' => 'vodacom',
        'amount' => 10,
        'reference' => 'EXAMPLE-AIRTIME-001',
    ]);

    // Store this ID with your order before checking the status in a live integration.
    $requestId = $queued['request_id'] ?? throw new RuntimeException('Missing airtime request ID.');
    $query = http_build_query(['request_id' => $requestId]);
    $order = requestJson("$url?$query", $auth);

    printJson(['queued' => $queued, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
