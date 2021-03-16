<?php

namespace Apurata\Financing\Controller\Order;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

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
        $url = ConfigData::APURATA_ADD_ON . urlencode($cart->getGrandTotal()) . '?page=' . $page;

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
