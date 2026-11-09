<?php

/**
 * Read the SIMcloud wallet balance using the account's API token.
 *
 * @link https://simcloud.co.za/api/balance.html
 */

declare(strict_types=1);

require __DIR__ . '/http.php';

try {
    $balance = requestJson('https://simcloud.co.za/api/balance.php', simcloudAuthorization());

    if ($balance['status'] !== 'success' || !is_numeric($balance['balance'] ?? null)) {
        throw new RuntimeException('SIMcloud did not return a wallet balance.');
    }

    // This is the last known balance. Keep its update time with the amount.
    printJson([
        'balance' => $balance['balance'],
        'updated_at' => $balance['updated_at'] ?? null,
    ]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
