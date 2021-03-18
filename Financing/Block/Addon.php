<?php
namespace Apurata\Financing\Block;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;

class Addon extends \Magento\Framework\View\Element\Template
{
    public function __construct (
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\SessionFactory $session,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\Registry $registry,
        RequestBuilder $requestBuilder,
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->requestBuilder = $requestBuilder;
        parent::__construct($context, $data);
	}

    public function getCurrentProduct() {
        return $this->_registry->registry('current_product');
    }
    public function getApurataAddon($page) {
        $cart = $this->session->create()->getQuote();
        $total = $cart->getGrandTotal();
        $product = $this->getCurrentProduct();
        $current_url = $this->_urlBuilder->getCurrentUrl();
        if ($page == 'product' && $product) {
            $total = $product->getFinalPrice();
        } else {
            $total = $cart->getGrandTotal();
        }
        $url = ConfigData::APURATA_ADD_ON . urlencode($total);
        $number_of_items = $cart->getItemsQty();
        $current_user = $this->customerSession->create()->getCustomer();

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
        error_log($url);
        list($respCode, $payWithApurataAddon) = $this->requestBuilder->makeCurlToApurata("GET", $url);
		if ($respCode == 200) {
            $addon = str_replace(array("\r", "\n"), '', $payWithApurataAddon);
		} else {
            $addon = '';
		}
        return $addon;

    }
}
