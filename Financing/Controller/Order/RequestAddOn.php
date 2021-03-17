<?php

namespace Apurata\Financing\Controller\Order;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableProduct;
use Magento\Framework\App\ObjectManager as ObjectManager;

class RequestAddOn extends Action
{
    public function __construct(
        Context $context,
        RequestBuilder $requestBuilder,
        Session $session,
        JsonFactory $resultJsonFactory
    ) {
        $this->session = $session;
        $this->requestBuilder = $requestBuilder;
        $this->resultJsonFactory = $resultJsonFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $cart = $this->session->getQuote();
        $page = $this->getRequest()->getParam('page');
        $oject_manager = ObjectManager::getInstance();
        $product = $oject_manager->get('Magento\Framework\Registry')->registry('current_product');
        $url_interface = $oject_manager->get('Magento\Framework\UrlInterface');
        $current_url = $url_interface->getUrl();
        if ($page == 'product' && $product) {
            $total = $product->getFinalPrice();
        } else {
            $total = $cart->getGrandTotal();
        }
        $url = ConfigData::APURATA_ADD_ON . urlencode($total);
        $number_of_items = $cart->getItemsCount();
        $current_user = $oject_manager->get('\Magento\Customer\Model\Session');
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
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData(['addon' => $addon]);
    }
}
