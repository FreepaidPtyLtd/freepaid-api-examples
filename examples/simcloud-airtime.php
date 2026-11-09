<?php

/**
 * Queue an airtime order and check its delivery using local sample responses.
 *
 * @link https://simcloud.co.za/api/airtime.html
 */

declare(strict_types=1);

require __DIR__ . '/http.php';

// The loopback endpoint returns fixtures and cannot reach the SIMcloud wallet.
$url = 'http://127.0.0.1:8080/api/airtime.php';
$auth = 'Authorization: Bearer sample-token';

try {
    $queued = requestJson($url, $auth, [
        'msisdn' => '0821234567',
        'network' => 'vodacom',
        'amount' => 10,
        'reference' => 'EXAMPLE-AIRTIME-001',
    ]);

    // SIMcloud's 201 response confirms queuing. Save the ID before checking delivery.
    $requestId = $queued['request_id'] ?? null;
    if (!is_int($requestId) || $requestId < 1) {
        throw new RuntimeException('SIMcloud did not return a valid airtime request ID.');
    }

    $query = http_build_query(['request_id' => $requestId]);
    $order = requestJson("$url?$query", $auth);

    printJson(['queued' => $queued, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
