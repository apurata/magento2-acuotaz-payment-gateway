<?php
namespace Apurata\Financing\Plugin\Checkout\CustomerData;

use Apurata\Financing\Helper\ConfigData;
 
class Cart {

    public function get_all_pos($amount) {
        $params = json_encode(array('landing_version'=>ConfigData::APURATA_LANDING_VERSION, 'extra_amounts'=>array(floatval($amount))));
        $ch = curl_init();

        $url = ConfigData::APURATA_DOMAIN.ConfigData::APURATA_SIMULATOR;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $all_pos = curl_exec($ch);
        $all_pos = json_decode($all_pos);
        curl_close($ch);
        return $all_pos;
    }

    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result)
    {
        $extra_data = '';
        if (floatval($result['subtotalAmount']) >= 100 && floatval($result['subtotalAmount']) <= 600){
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
        }
        $result['extra_data'] = $extra_data;
        return $result;
    }
}