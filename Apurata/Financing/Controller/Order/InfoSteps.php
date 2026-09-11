<?php

namespace Apurata\Financing\Controller\Order;

use Apurata\Financing\Helper\ConfigReader;
use Apurata\Financing\Helper\ErrorHandler;
use Apurata\Financing\Helper\RequestBuilder;
use Apurata\Financing\Model\Financing;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Proxy Apurata info-steps HTML through Magento (same-origin).
 * Avoids checkout CSP connect-src blocks and HTTPS→HTTP mixed content
 * when the browser would call apurata.com directly.
 */
class InfoSteps extends Action
{
    public function __construct(
        Context $context,
        private RequestBuilder $requestBuilder,
        private ConfigReader $configReader,
        private JsonFactory $resultJsonFactory,
        private Financing $financing,
        private ErrorHandler $errorHandler
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->errorHandler->neverRaise(function () {
            $resultJson = $this->resultJsonFactory->create();
            if (!$this->financing->isAvailable()) {
                return $resultJson->setData(['info_steps' => '']);
            }
            $clientId = $this->configReader->getClientId();
            if (!$clientId) {
                return $resultJson->setData(['info_steps' => '']);
            }
            $path = '/pos/' . rawurlencode($clientId) . '/info-steps';
            $apiResult = $this->requestBuilder->makeCurlToApurata('GET', $path);
            $html = ($apiResult['http_code'] == 200)
                ? str_replace(["\r", "\n"], '', $apiResult['response_raw'])
                : '';
            return $resultJson->setData(['info_steps' => $html]);
        }, 'InfoSteps');
    }
}
