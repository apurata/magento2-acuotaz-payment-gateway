<?php

namespace Apurata\Financing\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;
use Apurata\Financing\Helper\ConfigData;


class Config extends \Magento\Payment\Gateway\Config\Config
{
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlHelper,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->urlHelper = $urlHelper;
    }

    /**
     * Get Payment configuration status
     * 
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(ConfigData::KEY_ACTIVE);
    }

    /**
     * Get payment intent generation url
     * 
     * @return string
     */
    public function getFinancingIntentUrl()
    {
        return $this->urlHelper->getUrl(ConfigData::FINANCING_INTENT_PATH);
    }

    /**
     * Get Apurata client ID
     * 
     * @return string
     */
    public function getApurataClientId()
    {
        return (string) $this->getValue(ConfigData::KEY_APURATA_CLIENT_ID);
    }
}
