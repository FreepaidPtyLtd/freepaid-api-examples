<?php

/**
 * Look up the network for a South African mobile number.
 *
 * @link https://simcloud.co.za/api/network.html
 */

declare(strict_types=1);

require __DIR__ . '/http.php';

$msisdn = $argv[1] ?? '';

// Check the number's format locally. SIMcloud determines the network.
if (preg_match('/^(?:0|\+?27)\d{9}$/D', $msisdn) !== 1) {
    fail('Pass a South African mobile number in local or +27 format.');
}

try {
    // Encode the leading + in an international number so it is not read as a space.
    $query = http_build_query(['msisdn' => $msisdn]);
    $result = requestJson("https://simcloud.co.za/api/network.php?$query", simcloudAuthorization());

    if ($result['status'] !== 'success' || !is_string($result['network'] ?? null)) {
        throw new RuntimeException('SIMcloud did not return a mobile network.');
    }

    printJson($result);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
