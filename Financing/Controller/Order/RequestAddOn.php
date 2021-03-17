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
        error_log("ejecutando");
        $cart = $this->session->getQuote();
        $page = $this->getRequest()->getParam('page');
        $oject_manager = ObjectManager::getInstance();
        $registry = $oject_manager->get('\Magento\Framework\Registry');
        $product = $registry->registry('current_product');
        $url_interface = $oject_manager->get('Magento\Framework\UrlInterface');
        $current_url = $url_interface->getCurrentUrl();
        if ($page == 'product') {
            if ($product->getTypeId() == ConfigurableProduct::TYPE_CODE){
                $total = $product->getFinalPrice();
                error_log("IF");
            } else {
                $total = $product->getPrice();
                error_log("ELSE");
            }
        } else {
            $total = $cart->getGrandTotal();
        }
        $url = ConfigData::APURATA_ADD_ON . urlencode($total);
        $number_of_items = $cart->getItemsCount();
        error_log("a");
        $current_user = $oject_manager->get('Magento\Customer\Model\Session');
        error_log("b");
        $url .=
            '?page=' . urlencode($page) .
            '&continue_url=' . urlencode($current_url)
        ;
        error_log("A");
        if ($page =='cart' && $number_of_items > 1) {
            $url .= '&multiple_products=' . urlencode('TRUE');
        }
        error_log("B");
        if ($product) {
            $url .= '&product__id=' . urlencode($product->getId()) .
                '&product__name=' . urlencode($product->getName());
        }
        error_log("C");
        if ($current_user) {
            $url .= '&user__id=' . urlencode((string) $current_user->getId()) .
                '&user__email=' . urlencode((string) $current_user->getEmail()) .
                '&user__first_name=' . urlencode((string) $current_user->getName()) .
                '&user__last_name=' . urlencode((string) $current_user->getLastname());
        }
        error_log($url);
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
