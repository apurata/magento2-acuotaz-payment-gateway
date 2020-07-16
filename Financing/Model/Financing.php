<?php

namespace Apurata\Financing\Model;

use Apurata\Financing\Helper\ConfigData;
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
    }

    public function get_landing_config() {
        if (!$this->landing_config) {
            $ch = curl_init();

            $url = ConfigData::APURATA_DOMAIN.ConfigData::APURATA_LANDING_CONFIG;
            curl_setopt($ch, CURLOPT_URL, $url);

            $secret_token = $this->_scopeConfig->getValue(ConfigData::SECRET_TOKEN_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
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
        if (!ConfigData::ALLOW_HTTP && $_SERVER['REQUEST_SCHEME'] != 'https') {
            return False;
        }
        if( $this->_storeManager->getStore()->getCurrentCurrency()->getCode() != 'PEN' ) {
            return False;
        }
        $landing_config = $this->get_landing_config();
        if ($quote->getGrandTotal() < $landing_config->min_amount || $quote->getGrandTotal() > $landing_config->max_amount) {
            return False; 
        }
        return True;
    }

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
