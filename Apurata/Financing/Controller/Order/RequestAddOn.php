<?php

namespace Apurata\Financing\Controller\Order;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Apurata\Financing\Model\Financing;
use Apurata\Financing\Helper\ErrorHandler;

class RequestAddOn extends Action
{
    public function __construct(
        Context $context,
        private RequestBuilder $requestBuilder,
        private Session $session,
        private JsonFactory $resultJsonFactory,
        private Financing $financing,
        private ErrorHandler $errorHandler
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->errorHandler->neverRaise(function () {
            $resultJson = $this->resultJsonFactory->create();
            if (!$this->financing->isAvailable()) {
                return $resultJson->setData(['addon' => '']);
            }
            $cart = $this->session->getQuote();
            $page = $this->getRequest()->getParam('page');
            $total = $this->getRequest()->getParam('total');
            if (!$total) {
                $total = $cart->getGrandTotal();
            }
            $number_of_items = $cart->getItemsQty();
            $url = ConfigData::APURATA_ADD_ON . urlencode($total) . '?page=' . $page;
            if ($page == 'cart' && $number_of_items > 1) {
                $url .= '&multiple_products=' . urlencode('TRUE');
            }
            $apiResult = $this->requestBuilder->makeCurlToApurata("GET", $url);
            if ($apiResult['http_code'] == 200) {
                $addon = str_replace(array("\r", "\n"), '', $apiResult['response_raw']);
            } else {
                $addon = '';
            }
            return $resultJson->setData(['addon' => $addon]);
        }, 'RequestAddOn');
    }
}
