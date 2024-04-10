<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;


class Cancelation extends Action
{
    public function __construct(
        Context $context,
        private CheckoutSession $checkoutSession
    ) {
        return parent::__construct($context);
    }

    public function execute()
    {
        $this->checkoutSession->restoreQuote();
        $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
