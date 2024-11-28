<?php

namespace Apurata\Financing\Helper;

use Apurata\Financing\Helper\ConfigReader;
use Apurata\Financing\Helper\ConfigData;


class RequestBuilder
{
    public function __construct(
        private ConfigReader $configReader
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

        if (strtoupper($method) == "GET") {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } else if (strtoupper($method) == "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
        } else {
            throw new Exception('Method not supported: ' . $method);
        }

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

        $ret = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($httpCode, $ret);
    }
}
