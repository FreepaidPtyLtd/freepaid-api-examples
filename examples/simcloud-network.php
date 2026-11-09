<?php

declare(strict_types=1);

require __DIR__ . '/http.php';

$msisdn = $argv[1] ?? '';
if (!preg_match('/^(?:0|\+?27)\d{9}$/D', $msisdn)) {
    fail('Pass a South African mobile number in local or +27 format.');
}

try {
    $query = http_build_query(['msisdn' => $msisdn]);
    $result = requestJson("https://simcloud.co.za/api/network.php?$query", simcloudAuthorization());
    if ($result['status'] !== 'success' || !is_string($result['network'] ?? null)) {
        throw new RuntimeException('SIMcloud did not return a mobile network.');
    }

    printJson($result);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
