<?php

namespace Apurata\Financing\Controller\Order;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Apurata\Financing\Helper\ConfigData;


class Intent extends Action
{
    public function __construct(
        Context $context,
        private LoggerInterface $logger,
        private CheckoutSession $checkoutSession,
        private UrlInterface $urlBuilder,
        private \Magento\Customer\Model\Session $customerSession2
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        try {
            if (!$this->customerSession2->getApurataId()) {
                $this->customerSession2->setApurataId($this->customerSession2->getSessionId());
            }
        } catch (\Throwable $e) {
            error_log('Error:can not get session_id');
        }
        $session_id = $this->customerSession2->getApurataId();
        $intentParams = '?pos_client_id=' . $this->getRequest()->getParam('pos_client_id') .
            '&order_id=' . urlencode($order->getId()) .
            '&amount=' . urlencode($order->getGrandTotal()) .
            '&url_redir_on_canceled=' . urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/')) .
            '&url_redir_on_rejected=' . urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/')) .
            '&url_redir_on_success=' . urlencode(rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_SUCCESS_URL . $order->getId()), '/'));
        $billing_address = $order->getBillingAddress();
        if ($billing_address) {
            $intentParams .= '&customer_data__email=' . urlencode($billing_address->getData('email')) .
                '&customer_data__phone=' . urlencode($billing_address->getData('telephone')) .
                '&customer_data__billing_address_1=' . urlencode($billing_address->getData('street')) .
                '&customer_data__billing_first_name=' . urlencode($billing_address->getData('firstname')) .
                '&customer_data__billing_last_name=' . urlencode($billing_address->getData('lastname')) .
                '&customer_data__billing_city=' . urlencode($billing_address->getData('city'));
        }
        $shipping_address = $order->getShippingAddress();
        if ($shipping_address) {
            $intentParams .= '&customer_data__shipping_address_1=' . urlencode($shipping_address->getData('street')) .
                '&customer_data__shipping_first_name=' . urlencode($shipping_address->getData('firstname')) .
                '&customer_data__shipping_last_name=' . urlencode($shipping_address->getData('lastname')) .
                '&customer_data__shipping_city=' . urlencode($shipping_address->getData('city')) .
                '&customer_data__dni=' . urlencode($this->get_dni_field_id($order));
        }
        $intentParams .= '&customer_data__session_id=' . urldecode($session_id);


        $this->checkoutSession->restoreQuote();
        $this->_redirect(ConfigData::APURATA_DOMAIN . ConfigData::APURATA_CREATE_ORDER_URL . $intentParams);
    }
    public function get_dni_field_id($order)
    {
        $dni = $order->getBillingAddress()->getData('dni') ??
            $order->getBillingAddress()->getData('DNI') ??
            $order->getBillingAddress()->getData('Dni') ??
            '';
        return $dni;
    }
}
