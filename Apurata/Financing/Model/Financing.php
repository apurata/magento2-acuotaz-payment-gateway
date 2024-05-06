<?php

namespace Apurata\Financing\Model;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\ConfigReader;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Payment\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;


class Financing extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $landing_config = null;

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
    }

    public function get_landing_config()
    {
        if (!$this->landing_config) {
            $ch = curl_init();

            $url = ConfigData::APURATA_DOMAIN . ConfigData::APURATA_LANDING_CONFIG;
            curl_setopt($ch, CURLOPT_URL, $url);
            $secret_token = $this->config_reader->getSecretToken();
            $headers = array("Authorization: Bearer " . $secret_token);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $landing_config = curl_exec($ch);
            $landing_config = json_decode($landing_config);
            curl_close($ch);
            $this->landing_config = $landing_config;
        }
        return $this->landing_config;
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
        $landing_config = $this->get_landing_config();
        if ($quote) {
            $order_total = $quote->getGrandTotal();
            if (!$landing_config || $order_total < $landing_config->min_amount || $order_total > $landing_config->max_amount) {
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
