<?php

namespace Apurata\Financing\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const KEY_APURATA_CLIENT_ID = 'apurata_client_id';
    const FINANCING_INTENT_PATH = 'apuratafinancing/order/intent';
    const APURATA_POS_URL = 'http://localhost:8000/pos/crear-orden-y-continuar';

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Encryptor $encryptor
     * @param UrlInterface $urlHelper
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Encryptor $encryptor,
        UrlInterface $urlHelper,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);

        $this->encryptor = $encryptor;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Get Payment configuration status
     * 
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get payment intent generation url
     * 
     * @return string
     */
    public function getFinancingIntentUrl()
    {
        return $this->urlHelper->getUrl(self::FINANCING_INTENT_PATH);
    }

    /**
     * Get Apurata client ID
     * 
     * @return string
     */
    public function getApurataClientId()
    {
        return (string) $this->getValue(self::KEY_APURATA_CLIENT_ID);
    }

    /**
     * Get url for financing creation
     * 
     * @return string
     */
    public function getFinancingCreationUrl()
    {
        return self::APURATA_POS_URL; 
    }
}
