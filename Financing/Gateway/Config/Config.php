<?php

namespace Apurata\Financing\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\UrlInterface;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const KEY_ACTIVE = 'active';
    const FINANCING_INTENT_PATH = 'apurata_financing/financingintent/generate';
    const APURATA_DOMAIN = 'https://apurata.com';
    const POS_ENDPOINT = '/pos/crear-orden-y-continuar';

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
     * Get url for financing creation
     * 
     * @return string
     */
    public function getFinancingCreationUrl()
    {
        return self::APURATA_DOMAIN . self::POS_ENDPOINT; 
    }
}
