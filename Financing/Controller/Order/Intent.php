<?php

namespace Apurata\Financing\Controller\Order;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ObjectManager;
use Magento\Checkout\Model\Cart;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order;

class Intent extends Action
{
    const FINANCING_FAIL_URL = 'checkout/#payment';
    const FINANCING_SUCCESS_URL = 'checkout/onepage/success/';
    const MAGENTO_ORDERS_URL = 'sales/order/history/';

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        Cart $cart,
        CartManagementInterface $cartManagementInterface,
        Order $order
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->cart = $cart;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->order = $order;
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
        $quote = $this->cart->getQuote();
        $quote->setPaymentMethod('apurata_financing');
        $quote->getPayment()->importData(['method' => 'apurata_financing']);
        $quote->save();

        $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
        $order = $this->order->load($orderId);
        $order->setState('pending')->setStatus('pending');
        $order->save();

        /* $this->checkoutSession->restoreQuote(); */

        $customer = $this->customerSession->getCustomer();
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(['financingIntent' => [
                'order_id' => urlencode($orderId),
                'amount' => urlencode($quote->getGrandTotal()),
                'url_redir_on_canceled' => urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')),
                'url_redir_on_rejected' => urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')),
                'url_redir_on_success' => urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_SUCCESS_URL . $orderId), '/')),
                'customer_data__email' => urlencode($this->customerSession->getCustomer()->getEmail()),
                'customer_data__phone' => urlencode($this->getCustomerPhone($customer)),
                'customer_data__billing_first_name' => urlencode($this->customerSession->getCustomer()->getFirstname()),
                'customer_data__billing_last_name' => urlencode($this->customerSession->getCustomer()->getlastname())
            ]]);
        return $response;
    }
}
