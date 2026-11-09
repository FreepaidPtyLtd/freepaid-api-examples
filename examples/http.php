<?php

declare(strict_types=1);

function requestJson(string $url, string $authHeader, ?array $payload = null): array
{
    $endpoint = parse_url($url, PHP_URL_PATH);
    $headers = ['Accept: application/json', $authHeader];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_NOPROXY => '127.0.0.1',
    ];

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    $request = curl_init($url);
    curl_setopt_array($request, $options + [CURLOPT_HTTPHEADER => $headers]);
    $body = curl_exec($request);

    if ($body === false) {
        throw new RuntimeException("$endpoint: " . curl_error($request));
    }

    $status = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    $response = json_decode($body, true);

    if ($status === 401) {
        throw new RuntimeException("$endpoint: HTTP 401. Check your API credentials.");
    }

    if ($status < 200 || $status >= 300) {
        $message = $response['message'] ?? 'Unexpected API response';
        throw new RuntimeException("$endpoint: HTTP $status. $message");
    }

    if (!is_array($response)) {
        throw new RuntimeException("$endpoint: HTTP $status returned invalid JSON.");
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
