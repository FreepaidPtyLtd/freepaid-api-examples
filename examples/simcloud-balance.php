<?php

declare(strict_types=1);

require __DIR__ . '/http.php';

try {
    $balance = requestJson('https://simcloud.co.za/api/balance.php', simcloudAuthorization());

    printJson([
        'balance' => $balance['balance'],
        'updated_at' => $balance['updated_at'],
    ]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
