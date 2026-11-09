<?php

declare(strict_types=1);

function requestJson(string $url, string $authHeader, ?array $payload = null): array
{
    $operation = ($payload === null ? 'GET ' : 'POST ') . parse_url($url, PHP_URL_PATH);
    $headers = ['Accept: application/json', $authHeader];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
    ];

    if (parse_url($url, PHP_URL_HOST) === '127.0.0.1') {
        $options[CURLOPT_NOPROXY] = '127.0.0.1';
    }

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    $request = curl_init($url);
    if ($request === false || !curl_setopt_array($request, $options + [CURLOPT_HTTPHEADER => $headers])) {
        throw new RuntimeException("$operation: could not configure cURL.");
    }

    $body = curl_exec($request);

    if ($body === false) {
        throw new RuntimeException("$operation: " . curl_error($request));
    }

    $status = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    if ($status === 401) {
        throw new RuntimeException("$operation: HTTP 401. Check your API credentials.");
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("$operation: HTTP $status.");
    }

    $response = json_decode($body, true);
    if (!is_array($response) || !is_string($response['status'] ?? null)) {
        throw new RuntimeException("$operation: HTTP $status did not return a JSON object with a status.");
    }

    return $response;
}

function simcloudAuthorization(): string
{
    $token = trim((string) getenv('SIMCLOUD_API_TOKEN'));

    if ($token === '') {
        throw new RuntimeException('Set SIMCLOUD_API_TOKEN in your environment.');
    }

    return 'Authorization: Bearer ' . $token;
}

function printJson(array $response): void
{
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
