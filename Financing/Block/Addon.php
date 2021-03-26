<?php
namespace Apurata\Financing\Block;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Apurata\Financing\Model\Financing;
use Exception;

class Addon extends \Magento\Framework\View\Element\Template
{
    public function __construct (
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\SessionFactory $session,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\Registry $registry,
        RequestBuilder $requestBuilder,
        Financing $financing,
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->requestBuilder = $requestBuilder;
        $this->financing = $financing;
        parent::__construct($context, $data);
	}

    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }

    public function getApurataAddon($page) {
        if (!$this->financing->isAvailable()) {
            return '';
        }
        $cart = $this->session->create()->getQuote();
        $total = $cart->getGrandTotal();
        $product = $this->getCurrentProduct();
        $current_url = $this->_urlBuilder->getCurrentUrl();
        try{
            if ($page == 'product' && $product) {
                $total = $product->getFinalPrice();
            } else {
                $total = $cart->getGrandTotal();
            }
            $number_of_items = $cart->getItemsQty();
            $url = ConfigData::APURATA_ADD_ON . urlencode($total);
            $current_user = $this->customerSession->create()->getCustomer();
        } catch (\Throwable $e){
            error_log(sprintf("%s in file : %s line: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            return '';
        }
        $url .=
            '?page=' . urlencode($page) .
            '&continue_url=' . urlencode($current_url)
        ;
        if ($page =='cart' && $number_of_items > 1) {
            $url .= '&multiple_products=' . urlencode('TRUE');
        }
        if ($product) {
            $url .= '&product__id=' . urlencode($product->getId()) .
                '&product__name=' . urlencode($product->getName());
        }
        if ($current_user) {
            if($current_user->getId())
                $url .= '&user__id=' . urlencode($current_user->getId());
            if($current_user->getEmail())
                $url .= '&user__email=' . urlencode($current_user->getEmail());
            if($current_user->getName())
                $url .= '&user__first_name=' . urlencode($current_user->getName());
            if($current_user->getLastname())
                $url .= '&user__last_name=' . urlencode($current_user->getLastname());
        }
        list($respCode, $payWithApurataAddon) = $this->requestBuilder->makeCurlToApurata("GET", $url);
		if ($respCode == 200) {
            $addon = str_replace(array("\r", "\n"), '', $payWithApurataAddon);
		} else {
            $addon = '';
		}
        return $addon;
    }

    public function getApurataPixel() {
        $path = '/vendor/pixels/apurata-pixel.txt';
        $request_uri = ConfigData::APURATA_STATIC_DOMAIN . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_uri);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);    // seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // s
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) {
            error_log(sprintf('Apurata responded with http_code %s', $httpCode));
            return '';
        }
        else{
            return $response;
        }
    }

}
