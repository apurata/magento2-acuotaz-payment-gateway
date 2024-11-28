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


class Intent extends Action
{
    public function __construct(
        Context $context,
        private LoggerInterface $logger,
        private CheckoutSession $checkoutSession,
        private UrlInterface $urlBuilder,
        private RequestBuilder $requestBuilder,
        private \Magento\Customer\Model\Session $customerSession2
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $this->handleApurataId();
        $intentParams = $this->buildIntentParams($order);
        $this->checkoutSession->restoreQuote();
        list($respCode, $response) = $this->requestBuilder->makeCurlToApurata('POST', ConfigData::APURATA_CREATE_ORDER_URL, $intentParams);
        $response = json_decode($response);
        if ($respCode == 200) {
            $this->_redirect($response->redirect_to);
        } else {
            error_log(sprintf('Apurata log: Error al crear orden http_code %s', $respCode));
            $objectManager = ObjectManager::getInstance();
            $messageManager = $objectManager->get('Magento\Framework\Message\ManagerInterface');
            $messageManager->addErrorMessage('Hubo un error al procesar el pago con aCuotaz. Por favor, inténtelo de nuevo más tarde.');
            $this->_redirect($this->urlBuilder->getUrl('checkout'));
        }
    }

    private function handleApurataId()
    {
        try {
            if (!$this->customerSession2->getApurataId()) {
                $this->customerSession2->setApurataId($this->customerSession2->getSessionId());
            }
        } catch (\Throwable $e) {
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
        ];
        $intentParams = [
            'pos_client_id' => $this->getRequest()->getParam('pos_client_id'),
            'order_id' => $order->getId(),
            'amount' => $order->getGrandTotal(),
            'url_redir_on_canceled' => rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/'),
            'url_redir_on_rejected' => rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_FAIL_URL), '/'),
            'url_redir_on_success' => rtrim($this->urlBuilder->getUrl(ConfigData::FINANCING_SUCCESS_URL . $order->getId()), '/'),
            'customer_data' => $customerData,
        ];
        return $intentParams;
    }

    private function getDniFieldId($order)
    {
        $dni = $order->getBillingAddress()->getData('dni') ??
            $order->getBillingAddress()->getData('DNI') ??
            $order->getBillingAddress()->getData('Dni') ??
            '';
        return $dni;
    }
}
