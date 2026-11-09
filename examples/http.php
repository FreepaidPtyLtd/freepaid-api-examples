<?php

/**
 * Shared HTTP and CLI helpers for the API examples.
 */

declare(strict_types=1);

/**
 * Send a GET or a JSON POST and return the API response.
 *
 * The caller decides whether an API status such as "pending" is final.
 *
 * @param array<string, mixed>|null $payload Pass null for GET, or JSON fields for POST.
 * @return array<string, mixed> Decoded JSON containing the API status.
 * @throws RuntimeException If cURL, HTTP or response validation fails.
 * @throws JsonException If the request body cannot be encoded.
 */
function requestJson(string $url, string $authHeader, ?array $payload = null): array
{
    // Keep query values, credentials and response bodies out of diagnostics.
    $method = $payload === null ? 'GET' : 'POST';
    $operation = $method . ' ' . parse_url($url, PHP_URL_PATH);
    $headers = ['Accept: application/json', $authHeader];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
    ];

    // A shell proxy must never receive requests meant for the local mock.
    if (parse_url($url, PHP_URL_HOST) === '127.0.0.1') {
        $options[CURLOPT_NOPROXY] = '127.0.0.1';
    }

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
    }

    $options[CURLOPT_HTTPHEADER] = $headers;

    $request = curl_init($url);
    if ($request === false || !curl_setopt_array($request, $options)) {
        throw new RuntimeException("$operation: could not configure cURL.");
    }

    $body = curl_exec($request);

    if ($body === false) {
        throw new RuntimeException("$operation: " . curl_error($request));
    }

    // cURL can complete the transfer even when the API returns an HTTP error.
    $status = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    if ($status === 401) {
        throw new RuntimeException("$operation: HTTP 401. Check your API credentials.");
    }

    if ($status < 200 || $status >= 300) {
        throw new RuntimeException("$operation: HTTP $status.");
    }

    try {
        $response = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        throw new RuntimeException("$operation: HTTP $status returned invalid JSON.", 0, $error);
    }

    if (!is_array($response) || !is_string($response['status'] ?? null)) {
        throw new RuntimeException("$operation: HTTP $status did not return a JSON object with a status.");
    }

    return $response;
}

/**
 * Build the live SIMcloud bearer header from the shell environment.
 *
 * @throws RuntimeException If the token is missing or contains a line break.
 */
function simcloudAuthorization(): string
{
    $token = trim((string) getenv('SIMCLOUD_API_TOKEN'));

    if ($token === '') {
        throw new RuntimeException('Set SIMCLOUD_API_TOKEN in your environment.');
    }

    if (str_contains($token, "\r") || str_contains($token, "\n")) {
        throw new RuntimeException('SIMCLOUD_API_TOKEN must be a single line.');
    }

    return 'Authorization: Bearer ' . $token;
}

/**
 * Write one JSON document to standard output so it can be piped to another command.
 *
 * @param array<string, mixed> $response Fields selected by the example.
 * @throws JsonException If the output cannot be encoded.
 */
function printJson(array $response): void
{
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
}

/**
 * Report a CLI failure on standard error and exit with a nonzero status.
 */
function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
