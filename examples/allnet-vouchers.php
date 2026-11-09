<?php

/**
 * Place an AllNet order and fetch its voucher PINs from the local mock.
 *
 * @link https://allnetairtime.co.za/api/docs/
 */

declare(strict_types=1);

require __DIR__ . '/http.php';

$baseUrl = 'http://127.0.0.1:8080/api';
$auth = 'X-API-Key: sample-key';

try {
    // AllNet's value is the face value of one voucher in whole rand.
    $placed = requestJson("$baseUrl/order.php", $auth, [
        'quantity' => 2,
        'value' => 10,
        'description' => 'Example reward order',
    ]);

    // The POST returns the order details. The subsequent GET returns the PINs.
    $orderId = $placed['data']['order_id'] ?? null;
    if (!is_int($orderId) || $orderId < 1) {
        throw new RuntimeException('AllNet did not return a valid voucher order ID.');
    }

    $query = http_build_query(['order_id' => $orderId]);
    $order = requestJson("$baseUrl/fetch_order.php?$query", $auth);

    printJson(['placed' => $placed, 'order' => $order]);
} catch (RuntimeException | JsonException $error) {
    fail($error->getMessage());
}
