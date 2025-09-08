<?php

namespace Apurata\Financing\Controller\Debug;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class LogRequest extends Action
{
    public function __construct(
        Context $context,
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $request = $this->getRequest();
        $headers = [];
        $sensitiveHeaders = ['cookie', 'x-auth-token', 'session-token'];
        foreach ($request->getHeaders() as $header) {
            $headerName = strtolower($header->getFieldName());
            if (!in_array($headerName, $sensitiveHeaders)) {
                $headers[$header->getFieldName()] = $header->getFieldValue();
            } else {
                $headers[$header->getFieldName()] = '[FILTERED]';
            }
        }
        $requestData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'headers' => $headers,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'params' => $request->getParams(),
            'body' => $request->getContent(),
            'client_ip' => $request->getClientIp(),
            'user_agent' => $request->getHeader('User-Agent'),
            'referer' => $request->getHeader('Referer'),
            'is_secure' => $request->isSecure(),
            'is_ajax' => $request->isAjax(),
            'request_uri' => $request->getRequestUri(),
            'base_url' => $request->getBaseUrl()
        ];
        $response->setData($requestData);
        return $response;
    }
}
