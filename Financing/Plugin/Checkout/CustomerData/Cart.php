<?php
namespace Apurata\Financing\Plugin\Checkout\CustomerData;

use Apurata\Financing\Helper\ConfigData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

 
class Cart {

    protected $_scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    public function getAddOn($amount) {
        $ch = curl_init();
        $secret_token = $this->_scopeConfig->getValue(ConfigData::SECRET_TOKEN_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $url = ConfigData::APURATA_DOMAIN.ConfigData::APURATA_ADD_ON.urlencode($amount);
        $headers = array("Authorization: Bearer " . $secret_token);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        
        $addOn = curl_exec($ch);
        curl_close($ch);
        return $addOn;
    }

    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        $addOn = $this->getAddOn($result['subtotalAmount']);
        $result['extra_data'] = $addOn;
        return $result;
    }
}