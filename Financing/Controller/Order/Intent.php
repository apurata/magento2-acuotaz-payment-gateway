<?php

namespace Apurata\Financing\Controller\Order;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\Controller\ResultFactory;

class Intent extends Action
{
    const FINANCING_FAIL_URL = 'apuratafinancing/order/cancelation';
    const FINANCING_SUCCESS_URL = 'checkout/onepage/success/';
    const MAGENTO_ORDERS_URL = 'sales/order/history/';

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        return parent::__construct($context);
    }

    private function getCustomerPhone($customer)
    {
        $addressRepositoryInterface = $this->_objectManager->get('\Magento\Customer\Api\AddressRepositoryInterface');
        $billingAddressId = $customer->getDefaultBilling();
        $billingAddress = $addressRepositoryInterface->getById($billingAddressId);
        return $billingAddress->getTelephone();
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();;
        $customer = $this->customerSession->getCustomer();

        $intentParams = '?pos_client_id='.$this->getRequest()->getParam('pos_client_id').
            '&order_id='.urlencode($order->getId()).
            '&amount='.urlencode($order->getGrandTotal()).
            '&url_redir_on_canceled='.urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')).
            '&url_redir_on_rejected='.urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')).
            '&url_redir_on_success='.urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_SUCCESS_URL . $order->getId()), '/')).
            '&customer_data__email='.urlencode($this->customerSession->getCustomer()->getEmail()).
            '&customer_data__phone='.urlencode($this->getCustomerPhone($customer)).
            '&customer_data__billing_first_name='.urlencode($this->customerSession->getCustomer()->getFirstname()).
            '&customer_data__billing_last_name='.urlencode($this->customerSession->getCustomer()->getlastname());

        $this->_redirect('http://localhost:8000/pos/crear-orden-y-continuar'.$intentParams);
    }
}
