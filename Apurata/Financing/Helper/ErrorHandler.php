<?php

namespace Apurata\Financing\Helper;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class ErrorHandler
{
    public function __construct(
        private ConfigReader $configReader,
        private ProductMetadataInterface $productMetadata,
        private ModuleListInterface $moduleList
    ) {}

    public function neverRaise(callable $callback, string $context = '', $defaultValue = null)
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logError($e);
            $this->sendToSentry("Error en {$context}", $e);
            return $defaultValue;
        }
    }

    public function logError(\Throwable $e): void
    {
        error_log(sprintf(
            "Apurata log error: %s in file : %s line: %s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
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

    public function getExceptionPayload(\Throwable $exception): array
    {
        return [[
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

    public function unsafeSendToSentry(string $message, ?\Throwable $exception = null, $apiContext = null): void
    {
        $dsn = $this->configReader->getSentryDsn();
        if (!$dsn) { return; }
        $parsed = parse_url($dsn);
        $endpoint = "https://{$parsed['host']}/api/" . ltrim($parsed['path'], '/') . "/store/";
        $payload = $this->getSentryPayload($message);
        if ($exception) {
            $payload['exception'] = $this->getExceptionPayload($exception);
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
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "X-Sentry-Auth: Sentry sentry_version=7, sentry_client=apurata-magento/1.0, sentry_key={$parsed['user']}",
            ],
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
            $this->unsafeSendToSentry($message, $exception, $apiContext);
        } catch (\Throwable $e) {
            $this->logError($e);
        }
    }
}