<?php
namespace Apurata\Financing\Plugin\Checkout\CustomerData;

use Apurata\Financing\Helper\ConfigData;
 
class Cart {

    public function getAddOn($amount) {
        $ch = curl_init();
        $url = ConfigData::APURATA_DOMAIN.ConfigData::APURATA_ADD_ON.urlencode($amount);
        curl_setopt($ch, CURLOPT_URL, $url);
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