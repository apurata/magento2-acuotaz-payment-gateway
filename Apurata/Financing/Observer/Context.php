<?php

namespace Apurata\Financing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

class Context implements ObserverInterface {
    public function __construct(
        RequestBuilder $requestBuilder,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->productMetadata = $productMetadata;
        $this->_moduleList = $moduleList;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try{
            $client_id = $this->requestBuilder->configReader->getClientId();
            $mangento_version  = $this->productMetadata->getVersion();
            $plugin_version = $this->_moduleList->getOne('Apurata_Financing')['setup_version'];
            $url = "/pos/client/" . $client_id . "/context";
            $this->requestBuilder->makeCurlToApurata("POST", $url, array(
                "php_version"         => phpversion(),
                "magento_version"   => $mangento_version,
                "mg_acuotaz_version"  => $plugin_version,
            ), TRUE);
        } catch (\Throwable $e){
            error_log(sprintf("%s in file : %s line: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }

}