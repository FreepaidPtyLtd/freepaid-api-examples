<?php

/**
 * Check a meter, submit an electricity order and fetch its voucher locally.
 *
 * @link https://simcloud.co.za/api/electricity.html
 */

declare(strict_types=1);

require __DIR__ . '/http.php';

$url = 'http://127.0.0.1:8080/api/electricity.php';
$auth = 'Authorization: Bearer sample-token';

// Meter numbers are identifiers. A string preserves leading zeroes.
$meterNumber = '01234567890';
$reference = 'EXAMPLE-ELEC-001';

try {
    $query = http_build_query(['meter_number' => $meterNumber]);
    $meter = requestJson("$url?$query", $auth);

    // A null result means support is unknown. Require a confirmed match before submitting.
    if (($meter['supported'] ?? null) !== true) {
        throw new RuntimeException('SIMcloud did not confirm that this meter is supported.');
    }

    $accepted = requestJson($url, $auth, [
        'meter_number' => $meterNumber,
        'amount' => 150,
        'client_reference' => $reference,
        'recipient' => '0821234567',
        'send_sms' => false,
    ]);

    // With HTTP 202 the outcome is still unknown. Check this reference before buying again.
    $lookupReference = $accepted['client_reference'] ?? $reference;
    if (!is_string($lookupReference) || trim($lookupReference) === '') {
        throw new RuntimeException('SIMcloud did not return a valid electricity reference.');
    }

    $query = http_build_query(['client_reference' => $lookupReference]);
    $order = requestJson("$url?$query", $auth);

    printJson(['meter' => $meter, 'accepted' => $accepted, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
