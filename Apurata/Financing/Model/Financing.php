<?php

namespace Apurata\Financing\Model;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\ConfigReader;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\StoreManagerInterface;


class Financing extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $landingConfig = null;

    /**
     *  Overrides fields
     */
    protected $_code = 'apurata_financing';
    protected $_scopeConfig;
    protected $_storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        RequestBuilder $requestBuilder,
        ConfigReader $configReader,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->config_reader = $configReader;
        $this->requestBuilder = $requestBuilder;
    }

    public function getLandingConfig()
    {
        if (!$this->landingConfig) {
            list($respCode, $landingConfig) = $this->requestBuilder->makeCurlToApurata("GET", ConfigData::APURATA_LANDING_CONFIG);
            $landingConfig = json_decode($landingConfig);
            $this->landingConfig = ($respCode == 200) ? $landingConfig : null;
        }
        return $this->landingConfig;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->config_reader->allowHttp() && $_SERVER['REQUEST_SCHEME'] != 'https') {
            error_log('Apurata solo soporta https');
            return False;
        }
        $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        if ($currency != 'PEN') {
            error_log('Apurata sólo soporta currency=PEN. Currency actual=' . $currency);
            return False;
        }
        $landingConfig = $this->getLandingConfig();
        if ($quote) {
            $order_total = $quote->getGrandTotal();
            if (!$landingConfig || $order_total < $landingConfig->min_amount || $order_total > $landingConfig->max_amount) {
                error_log('Apurata no financia el monto del carrito: ' . $order_total);
                return False;
            }
        }
        return True;
    }

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
