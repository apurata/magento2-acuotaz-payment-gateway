<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Store\Model\ScopeInterface;
use Apurata\Financing\Helper\ConfigData;


class HandleEvent extends Action
{
    public function __construct(
        Context $context,
        private Cart $cart,
        private ScopeConfigInterface $scopeConfig,
        private Order $order,
        private OrderManagementInterface $orderManagement
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $event = $this->getRequest()->getParam('event');
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->loadOrder($orderId, $response);
        if (!$order) {
            return $response;
        }
        $auth = $this->checkAuthorization($response);
        if (!$auth) {
            return $response;
        }
        $comment = $this->processEvent($order, $event, $response);
        if ($comment) {
            $this->addStatusHistoryComment($order, $comment);
        }
        return $response;
    }

    private function loadOrder($orderId, $response)
    {
        $order = $this->order->load($orderId);
        if (!$order->getId()) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Order does not exist')]);
            return null;
        }
        return $order;
    }

    private function checkAuthorization($response)
    {
        $auth = $this->getRequest()->getHeader('Apurata-Auth');
        if (!$auth) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Not authorizedasda')]);
            return false;
        }
        list($auth_type, $token) = explode(' ', $auth);
        if (strtolower($auth_type) != 'bearer') {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Invalid authorization type')]);
            return false;
        }
        $secret_token = $this->scopeConfig->getValue(ConfigData::SECRET_TOKEN_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        if ($token != $secret_token) {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Invalid authorization token')]);
            return false;
        }
        return true;
    }

    private function processEvent($order, $event, $response)
    {
        switch ($event) {
            case 'onhold':
            case 'created':
                error_log('Evento ignorado por Apurata:' . $event);
                break;
            case 'validated':
            case 'approved':
                $comment = ($event == 'approved') ? 'Orden Calificada (Todavia no entregar producto)' : 'aCuotaz validó identidad';
                break;
            case 'rejected':
            case 'canceled':
                $comment = $this->handleRejectOrCancelEvent($order, $event, $response);
                break;
            case 'funded':
                $comment = $this->handleFundedEvent($order, $event, $response);
                break;
            default:
                $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
                $response->setData(['message' => __('Event not found')]);
                return null;
        }
        return $comment ?? '';
    }

    private function handleRejectOrCancelEvent($order, $event, $response)
    {
        if ($order->getStatus() != 'pending') {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Status order not pending ')]);
            return null;
        }
        $response->setData(['message' => __('Order successfully canceled')]);
        $comment = ($event == 'rejected') ? 'aCuotaz no aprobó el financiamiento' : 'El financiamiento en aCuotaz fue anulado';
        $order->cancel();
        return $comment;
    }

    private function handleFundedEvent($order, $event, $response)
    {
        if ($order->getStatus() != 'pending') {
            $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
            $response->setData(['message' => __('Status order not pending')]);
            return null;
        }
        $response->setData(['message' => __('Order successfully funded')]);
        $comment = 'aCuotaz notifica que esta orden fue pagada y ya se puede entregar';
        $order->setState('processing')->setStatus('processing');
        return $comment;
    }

    private function addStatusHistoryComment($order, $comment)
    {
        $order->addStatusHistoryComment(__($comment));
        $order->save();
    }
}
