<?php

declare(strict_types=1);

$fixtures = [
    'POST /api/airtime.php' => [201, 'airtime-queued.json'],
    'GET /api/airtime.php?request_id=1001' => [200, 'airtime-delivered.json'],
    'GET /api/electricity.php?meter_number=01234567890' => [200, 'electricity-meter.json'],
    'POST /api/electricity.php' => [201, 'electricity-accepted.json'],
    'GET /api/electricity.php?client_reference=EXAMPLE-ELEC-001_1783324800_000000000001' => [200, 'electricity-completed.json'],
    'GET /api/electricity.php?client_reference=EXAMPLE-ELEC-001' => [200, 'electricity-completed.json'],
    'POST /api/order.php' => [201, 'allnet-placed.json'],
    'GET /api/fetch_order.php?order_id=2001' => [200, 'allnet-vouchers.json'],
];

$route = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
header('Content-Type: application/json');

if (!isset($fixtures[$route])) {
    http_response_code(404);
    echo '{"status":"error","message":"No sample response for this method and URL."}';
    return;
}

[$status, $file] = $fixtures[$route];
http_response_code($status);
readfile(__DIR__ . '/samples/' . $file);
