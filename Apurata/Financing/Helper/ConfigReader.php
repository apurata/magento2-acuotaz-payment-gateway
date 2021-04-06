<?php

namespace Apurata\Financing\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigReader
{
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getIsActive() {
        return (bool) $this->scopeConfig->getValue('payment/apurata_financing/active', ScopeInterface::SCOPE_STORE);
    }

    public function getSecretToken() {
        return $this->scopeConfig->getValue('payment/apurata_financing/secret_token', ScopeInterface::SCOPE_STORE);
    }

    public function getClientId() {
        return $this->scopeConfig->getValue('payment/apurata_financing/apurata_client_id', ScopeInterface::SCOPE_STORE);
    }

    public function allowHttp() {
        return (bool) $this->scopeConfig->getValue('payment/apurata_financing/allow_http', ScopeInterface::SCOPE_STORE);
    }

}
