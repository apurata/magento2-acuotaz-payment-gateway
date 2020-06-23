<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Cart;

class HandleEvent extends Action
{
    const SECRET_TOKEN_CONFIG_PATH = 'payment/apurata_financing/secret_token';

    public function __construct(
        Context $context,
        Cart $cart,
        ScopeConfigInterface $scopeConfig,
        Order $order
    ) {
        $this->cart = $cart;
        $this->scopeConfig = $scopeConfig; 
        $this->order = $order;
        return parent::__construct($context);
    }

    public function execute()
    {  
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $event = $this->getRequest()->getParam('event');
        $orderId = $this->getRequest()->getParam('order_id');

        $order = $this->order->load($orderId);
        if (!$order->getId()) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Order does not exist')]);
            return $response;
        }

        // Check Authorization
        $auth = $this->getRequest()->getHeader('Authorization');
        if (!$auth) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Not authorized')]);
            return $response;
        }
        list($auth_type, $token) = explode(' ', $auth);
        if (strtolower($auth_type) != 'bearer'){
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Invalid authorization type')]);
            return $response;
        }

        $secret_token = $this->scopeConfig->getValue(
            self::SECRET_TOKEN_CONFIG_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($token != $secret_token) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Invalid authorization token')]);
            return $response;
        }

        if ($event == 'approved' && $order->getStatus() == 'pending') {
            $order->setState('holded')->setStatus('holded');
            $this->cart->truncate()->save();
        } else if ($event == 'validated') {
            $order->setState('processing')->setStatus('processing');
        } else if ($event == 'rejected') {
            $order->setState('canceled')->setStatus('canceled');
        } else if ($event == 'canceled') {
            $order->setState('canceled')->setStatus('canceled');
        } else {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Event not found')]);
            return $response;
        }
        $order->save();
        $response->setData(['message' => __('Request processed')]);
        return $response;
    }
}
