<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\Order;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Cart;

class PlaceOrder extends Action
{
    protected $_pageFactory;
    private $cart;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Cart $cart
    ) {
        $this->cart = $cart;
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {        
        $this->cart->truncate()->save();
        return $this->_pageFactory->create();
    }
}
