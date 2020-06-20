<?php

namespace Apurata\Financing\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Model\Order;

class PlaceOrder extends Action
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var cart
     */
    private $cart;

    /**
     * @var pageFactory
     */
    private $pageFactory;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        QuoteFactory $quoteFactory,
        CartManagementInterface $cartManagementInterface,
        CheckoutSession $checkoutSession,
        Cart $cart,
        Order $order,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->cart = $cart;
        $this->order = $order;
        $this->pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {      
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $quoteId = $this->getRequest()->getParam('quote_id');
        $quote = $this->quoteFactory->create()->load($quoteId);
        $quote->setPaymentMethod('apurata_financing');
        $quote->collectTotals();
        $quote->getPayment()->importData(['method' => 'apurata_financing']);
        $quote->save();
        
        try
        {
            $this->cartManagementInterface->placeOrder($quote->getId());
            $this->cart->truncate()->save();
            return $this->pageFactory->create();
        }
        catch (\Exception $e) {
            return $this->_redirect('');
            /*$this->messageManager->addExceptionMessage(
                $e,
                __('The order #%1 cannot be processed.', $quote->getReservedOrderId())
            );*/
        }
        return $this->_redirect('checkout/cart');
    }
}
