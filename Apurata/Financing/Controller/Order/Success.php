<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Model\OrderRepository;

class Success extends Action
{
    protected $checkoutSession;
    protected $orderRepository;
    protected $redirectFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderRepository $orderRepository,
        RedirectFactory $redirectFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->redirectFactory = $redirectFactory;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            if ($order && $order->getId()) {
                $this->checkoutSession->setLastOrderId($order->getId());
                $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
    }
}
