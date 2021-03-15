<?php

namespace Apurata\Financing\Controller\Order;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;
use Apurata\Financing\Helper\ConfigData;


class Intent extends Action
{
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder
    ) {
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        return parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        $intentParams = '?pos_client_id='.$this->getRequest()->getParam('pos_client_id').
            '&order_id='.urlencode($order->getId()).
            '&amount='.urlencode($order->getGrandTotal()).
            '&url_redir_on_canceled='.urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/')).
            '&url_redir_on_rejected='.urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/')).
            '&url_redir_on_success='.urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_SUCCESS_URL . $order->getId()), '/')).
            '&customer_data__email='.urlencode($order->getBillingAddress()->getData('email')).
            '&customer_data__phone='.urlencode($order->getBillingAddress()->getData('telephone')).
            '&customer_data__billing_address_1='.urlencode($order->getBillingAddress()->getData('street')) .
            '&customer_data__billing_first_name='.urlencode($order->getBillingAddress()->getData('firstname')).
            '&customer_data__billing_last_name='.urlencode($order->getBillingAddress()->getData('lastname')).
            '&customer_data__billing_city='.urlencode($order->getBillingAddress()->getData('city')).
            '&customer_data__shipping_address_1='.urlencode($order->getShippingAddress()->getData('street')).
            '&customer_data__shipping_first_name='.urlencode($order->getShippingAddress()->getData('firstname')).
            '&customer_data__shipping_last_name='.urlencode($order->getShippingAddress()->getData('lastname')).
            '&customer_data__shipping_city='.urlencode($order->getShippingAddress()->getData('city'));

        $this->_redirect(ConfigData::APURATA_DOMAIN.ConfigData::APURATA_CREATE_ORDER_URL.$intentParams);
    }
}
