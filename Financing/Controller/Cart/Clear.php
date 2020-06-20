<?php

namespace Apurata\Financing\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;

class Clear extends Action
{
    /**
     * @var Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     *
     */
    public function __construct(
        Context $context,
        Cart $cart,
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
		parent::__construct($context);
        $this->cart = $cart;
        $this->quoteFactory = $quoteFactory;
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {      
        $quote = $this->quoteFactory->create()->load(42);
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(['mess' => $this->checkoutSession->getLastRealOrder()->getIncrementId()]);
        return $response;
    }
}
