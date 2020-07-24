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
        /*if (floatval($result['subtotalAmount']) >= 100 && floatval($result['subtotalAmount']) <= 600){
            $all_pos = null;
            $all_pos = $this->get_all_pos($result['subtotalAmount']);
            $min_step = floatval($result['subtotalAmount']);
            if ($all_pos) {
                foreach ($all_pos as $pos) {
                    if ($pos->amount == $result['subtotalAmount'] && $pos->payment_igv < $min_step) {
                        $min_step = $pos->payment_igv;
                    }
                }
            }
            $extra_data = 'Pagalo en cuotas desde S/'.$min_step;
        }*/
        $result['extra_data'] = $addOn;
        return $result;
    }
}