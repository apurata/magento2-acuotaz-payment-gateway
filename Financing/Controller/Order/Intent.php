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

class Intent extends Action
{
    const FINANCING_FAIL_URL = 'checkout/#payment';
    const FINANCING_SUCCESS_URL = 'apuratafinancing/order/placeorder?quote_id=';
    const MAGENTO_ORDERS_URL = 'sales/order/history/';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var cart
     */
    private $cart;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var url
     */
    private $urlBuilder;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        UrlInterface $urlBuilder,
        Cart $cart
    ) {
        parent::__construct($context);

        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $urlBuilder;
        $this->cart = $cart;
    }

    /**
     * return client phone
     */
    private function getCustomerPhone($customer)
    {
        $addressRepositoryInterface = $this->_objectManager->get('\Magento\Customer\Api\AddressRepositoryInterface');
        $billingAddressId = $customer->getDefaultBilling();
        $billingAddress = $addressRepositoryInterface->getById($billingAddressId);
        return $billingAddress->getTelephone();
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $customer = $this->customerSession->getCustomer();
        
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(['financingIntent' => [
                'order_id' => urlencode($this->cart->getQuote()->getId()),
                'amount' => urlencode($this->cart->getQuote()->getGrandTotal()),
                'url_redir_on_canceled' => urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')),
                'url_redir_on_rejected' => urlencode(rtrim($this->urlBuilder->getUrl(self::FINANCING_FAIL_URL), '/')),
                'url_redir_on_success' => urlencode($this->urlBuilder->getUrl(self::FINANCING_SUCCESS_URL . $this->cart->getQuote()->getId())),
                'customer_data__email' => urlencode($this->customerSession->getCustomer()->getEmail()),
                'customer_data__phone' => urlencode($this->getCustomerPhone($customer)),
                'customer_data__billing_first_name' => urlencode($this->customerSession->getCustomer()->getFirstname()),
                'customer_data__billing_last_name' => urlencode($this->customerSession->getCustomer()->getlastname())
            ]]);
        return $response;
    }

    /**
     * Return response for bad request
     * @param ResultInterface $response
     * @return ResultInterface
     */
    private function processBadRequest(ResultInterface $response)
    {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $response->setData(['message' => __('Sorry, but something went wrong')]);

        return $response;
    }
}
