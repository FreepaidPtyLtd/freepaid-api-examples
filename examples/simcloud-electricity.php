<?php

declare(strict_types=1);

require __DIR__ . '/http.php';

$url = 'http://127.0.0.1:8080/api/electricity.php';
$auth = 'Authorization: Bearer sample-token';
$meterNumber = '01234567890';
$reference = 'EXAMPLE-ELEC-001';

try {
    $query = http_build_query(['meter_number' => $meterNumber]);
    $meter = requestJson("$url?$query", $auth);

    if (($meter['supported'] ?? null) !== true) {
        throw new RuntimeException($meter['message'] ?? 'Meter support was not confirmed.');
    }

    $accepted = requestJson($url, $auth, [
        'meter_number' => $meterNumber,
        'amount' => 150,
        'client_reference' => $reference,
        'recipient' => '0821234567',
        'send_sms' => false,
    ]);

    // The reference also lets you check a purchase still being reconciled.
    $query = http_build_query(['client_reference' => $accepted['client_reference'] ?? $reference]);
    $order = requestJson("$url?$query", $auth);

    printJson(['meter' => $meter, 'accepted' => $accepted, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
