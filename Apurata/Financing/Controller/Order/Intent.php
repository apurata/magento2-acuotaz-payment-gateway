<?php

namespace Apurata\Financing\Controller\Order;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Apurata\Financing\Helper\ErrorHandler;

class Intent extends Action
{
    public function __construct(
        Context $context,
        private LoggerInterface $logger,
        private CheckoutSession $checkoutSession,
        private UrlInterface $urlBuilder,
        private RequestBuilder $requestBuilder,
        private \Magento\Customer\Model\Session $customerSession2,
        private ErrorHandler $errorHandler
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->errorHandler->neverRaise(function () {
            $order = $this->checkoutSession->getLastRealOrder();
            $this->handleApurataId();
            $intentParams = $this->buildIntentParams($order);
            $this->checkoutSession->restoreQuote();
            $apiResult = $this->requestBuilder->makeCurlToApurata('POST', ConfigData::APURATA_CREATE_ORDER_URL, $intentParams);
            if ($apiResult['http_code'] == 200) {
                $this->_redirect($apiResult['response_json']->redirect_to);
            } else {
                error_log(sprintf('Apurata log: Error al crear orden http_code %s', $apiResult['http_code']));
                $objectManager = ObjectManager::getInstance();
                $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
                $messageManager->addErrorMessage('Hubo un error al procesar el pago con aCuotaz. Por favor, inténtelo de nuevo más tarde.');
                $this->_redirect($this->urlBuilder->getUrl('checkout'));
            }
        });
    }

    private function handleApurataId()
    {
        try {
            if (!$this->customerSession2->getApurataId()) {
                $this->customerSession2->setApurataId($this->customerSession2->getSessionId());
            }
        } catch (\Throwable $e) {
            $this->requestBuilder->sendToSentry('Cannot get session_id', $e);
            error_log('Apurata log: cannot get session_id');
        }
    }

    private function buildIntentParams($order)
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $customerData = [
            'email' => $billingAddress->getData('email'),
            'phone' => $billingAddress->getData('telephone'),
            'billing_address_1' => $billingAddress->getData('street'),
            'billing_first_name' => $billingAddress->getData('firstname'),
            'billing_last_name' => $billingAddress->getData('lastname'),
            'billing_city' => $billingAddress->getData('city'),
            'shipping_address_1' => $shippingAddress->getData('street'),
            'shipping_first_name' => $shippingAddress->getData('firstname'),
            'shipping_last_name' => $shippingAddress->getData('lastname'),
            'shipping_city' => $shippingAddress->getData('city'),
            'dni' => $this->getDniFieldId($order),
            'session_id' => $this->customerSession2->getApurataId(),
            'merchant_reference' => $order->getIncrementId(),
        ];
        $failUrl = $this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL);
        $successUrl = $this->urlBuilder->getUrl(
                ConfigData::FINANCING_SUCCESS_URL,
                ['_query' => ['order_id' => $order->getId(), 'store_code' => $order->getStore()->getCode()]]
            );
        $intentParams = [
            'pos_client_id' => $this->getRequest()->getParam('pos_client_id'),
            'order_id' => $order->getId(),
            'amount' => $order->getGrandTotal(),
            'url_redir_on_canceled' => $failUrl,
            'url_redir_on_rejected' => $failUrl,
            'url_redir_on_success' => $successUrl,
            'customer_data' => $customerData,
        ];
        return $intentParams;
    }

    private function getDniFieldId($order): string
    {
        $billingAddress = $order->getBillingAddress();
        foreach ($billingAddress->getData() as $key => $value) {
            if (strtolower($key) === 'dni' && !empty($value)) {
                return $value;
            }
        }
        return '';
    }
}
