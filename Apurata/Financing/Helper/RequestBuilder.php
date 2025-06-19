<?php

namespace Apurata\Financing\Helper;

use Apurata\Financing\Helper\ConfigReader;
use Apurata\Financing\Helper\ConfigData;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class RequestBuilder
{
    public function __construct(
        private ConfigReader $configReader,
        private ProductMetadataInterface $productMetadata,
        private ModuleListInterface $moduleList
    ) {}

    public function makeCurlToApurata($method, $path, $data = null, $fire_and_forget = false, $extra_headers = [])
    {
        $ch = curl_init();
        $url = ConfigData::APURATA_DOMAIN . $path;
        curl_setopt($ch, CURLOPT_URL, $url);
        // Timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);    // seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // seconds
        $headers = array('Authorization: Bearer ' . $this->configReader->getSecretToken());
        $headers = array_merge($headers, $extra_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $method = strtoupper($method);
        if ($method === "GET") {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
        } else {
            throw new Exception('Method not supported: ' . $method);
        }
        $payload = null;
        if ($data) {
            $payload = json_encode($data);
            // Attach encoded JSON string to the POST fields
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            // Set the content type to application/json
            array_push($headers, 'Content-Type:application/json');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($fire_and_forget) {
            // From: https://www.xspdf.com/resolution/52447753.html
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            // We don't use CURLOPT_TIMEOUT_MS because the name resolution fails and the
            // whole request never goes out
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }
        $response = curl_exec($ch);
        $apiResult = [
            'http_code'      => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'response_raw'   => $response,
            'response_json'  => json_decode($response),
            'url'            => $url,
            'method'         => $method,
            'request_body'   => $payload,
            'request_headers'=> $headers,
            'curl_error'     => curl_error($ch),
            'curl_errno'     => curl_errno($ch),
        ];
        curl_close($ch);
        if ($apiResult['http_code'] != 200) {
            $this->sendToSentry('Failed to make request to Apurata',  null, $apiResult);
        }
        return $apiResult;
    }

    public function getSentryPayload(string $message): array
    {
        return [
            'event_id'    => bin2hex(random_bytes(16)),
            'timestamp'   => gmdate('Y-m-d\TH:i:s'),
            'platform'    => 'php',
            'environment' => 'production',
            'level'       => 'error',
            'logger'      => 'apurata-magento',
            'message'     => $message,
            'tags'        => [
                'client_id' => $this->configReader->getClientId(),
            ],
            'server_name' => gethostname(),
            'user' => [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ],
            'contexts' => [
                'platform' => [
                    'php_version'       => PHP_VERSION,
                    'magento_version'   => $this->productMetadata->getVersion(),
                    'plugin_version'    => $this->moduleList->getOne('Apurata_Financing')['setup_version'],
                ],
                'runtime' => [
                    'name' => 'php',
                    'version' => PHP_VERSION,
                ],
                'os' => [
                    'name' => php_uname('s'),
                    'version' => php_uname('r'),
                ],
            ],
        ];
    }
    public function unsafesSendToSentry()
    {
        $dsn = $this->configReader->getSentryDsn();
        if (!$dsn) {
            return;
        }
        $parsed = parse_url($dsn);
        $publicKey = $parsed['user'];
        $host = $parsed['host'];
        $projectId = ltrim($parsed['path'], '/');
        $endpoint = "https://{$host}/api/{$projectId}/store/";
        $payload = $this->getSentryPayload($message);
        if ($exception) {
            $payload['exception'] = [[
                'type'  => get_class($exception),
                'value' => $exception->getMessage(),
                'stacktrace' => [
                    'frames' => array_map(function ($frame) {
                        return [
                            'filename' => $frame['file'] ?? '[internal]',
                            'function' => $frame['function'] ?? '[unknown]',
                            'lineno'   => $frame['line'] ?? 0,
                        ];
                    }, array_reverse($exception->getTrace()))
                ]
            ]];
        }
        if ($apiContext) {
            $httpCode = $apiContext['http_code'] ?? 0;
            $payload['tags']['http_status_group'] = floor($httpCode / 100) . 'xx';
            $payload['request'] = [
                'url'     => $apiContext['url'] ?? '',
                'method'  => $apiContext['method'] ?? '',
                'data'    => $apiContext['request_body'] ?? null,
                'headers' => array_map(fn($h) => array_map('trim', explode(':', $h, 2)), $apiContext['request_headers']) ?? [],
            ];
            $payload['contexts']['response'] = [
                'status_code' => $httpCode,
                'body'        => $apiContext['response_json'] ?? $apiContext['response_raw'] ?? '',
            ];
            $payload['contexts']['curl'] = [
                'error'  => $apiContext['curl_error'] ?? '',
                'errno'  => $apiContext['curl_errno'] ?? 0,
            ];
        }
        $headers = [
            'Content-Type: application/json',
            "X-Sentry-Auth: Sentry sentry_version=7, sentry_client=apurata-magento/1.0, sentry_key={$publicKey}",
        ];
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public function sendToSentry(string $message, ?\Throwable $exception = null, $apiContext = null): void
    {
        try {
            $this->unsafesSendToSentry($message, $exception, $apiContext);
        } catch (\Throwable $e) {
            error_log(sprintf(
                "Apurata log: %s in file : %s line: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }
}