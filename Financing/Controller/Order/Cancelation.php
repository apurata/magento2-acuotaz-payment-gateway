<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Model\Order;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;

class Cancelation extends Action
{
    protected $_pageFactory;
    private $cart;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->_pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->checkoutSession->restoreQuote();
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
