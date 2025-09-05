<?php

namespace Apurata\Financing\Controller\Debug;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class LogRequest extends Action
{
    public function __construct(
        Context $context,
        private LoggerInterface $logger
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $request = $this->getRequest();
        $headers = [];
        foreach ($request->getHeaders() as $header) {
            $headers[$header->getFieldName()] = $header->getFieldValue();
        }

        $requestData = [
            'headers' => $headers,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'params' => $request->getParams(),
            'body' => $request->getContent()
        ];

        $response->setData($requestData);
        return $response;
    }
}
