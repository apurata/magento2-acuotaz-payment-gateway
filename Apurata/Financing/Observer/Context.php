<?php

namespace Apurata\Financing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Apurata\Financing\Helper\ErrorHandler;

class Context implements ObserverInterface
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ProductMetadataInterface $productMetadata,
        private ModuleListInterface $moduleList,
        private ErrorHandler $errorHandler
    ) {
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        return $this->errorHandler->neverRaise(function () {
            $client_id = $this->requestBuilder->configReader->getClientId();
            $magento_version = $this->productMetadata->getVersion();
            $plugin_version = $this->moduleList->getOne('Apurata_Financing')['setup_version'];
            $url = "/pos/client/" . $client_id . "/context";
            $this->requestBuilder->makeCurlToApurata("POST", $url, [
                "php_version"        => phpversion(),
                "magento_version"    => $magento_version,
                "mg_acuotaz_version" => $plugin_version,
            ], true);
        }, '');
    }
}
