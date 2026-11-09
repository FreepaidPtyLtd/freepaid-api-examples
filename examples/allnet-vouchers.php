<?php

declare(strict_types=1);

require __DIR__ . '/http.php';

$baseUrl = 'http://127.0.0.1:8080/api';
$auth = 'X-API-Key: sample-key';

try {
    $placed = requestJson("$baseUrl/order.php", $auth, [
        'quantity' => 2,
        'value' => 10,
        'description' => 'Example reward order',
    ]);

    $orderId = $placed['data']['order_id'] ?? throw new RuntimeException('Missing AllNet order ID.');
    $query = http_build_query(['order_id' => $orderId]);
    $order = requestJson("$baseUrl/fetch_order.php?$query", $auth);

    printJson(['placed' => $placed, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
