<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Checkout\Model\Cart;

class Create extends Action
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
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        QuoteFactory $quoteFactory,
        CartManagementInterface $cartManagementInterface,
        CheckoutSession $checkoutSession,
        Cart $cart,
        \Magento\Sales\Model\Order $order
    ) {
        parent::__construct($context);
        
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->cart = $cart;
        $this->order = $order;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {      
        $quoteId = $this->getRequest()->getParam('quote_id');

        $quote = $this->quoteFactory->create()->load($quoteId);
        $quote->setPaymentMethod('apurata_financing');
        $quote->save();
        $quote->getPayment()->importData(['method' => 'apurata_financing']);
        
        // Collect Totals & Save Quote
        $quote->collectTotals()->save();
        
        $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
        $order = $this->order->load($orderId);
        
        $order->setEmailSent(0);
        $increment_id = $order->getRealOrderId();

        if($order->getEntityId())
        {
            $result['order_id']= $order->getRealOrderId();
            
            // Clear cart
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
            $cartObject = $objectManager->create('Magento\Checkout\Model\Cart')->truncate(); 
            $cartObject->saveQuote();

            //redirect ty page
            $this->_redirect('checkout/onepage/success/');
        }
        else
        {
            $result=['error'=>1,'msg'=>'Your custom message'];
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(['Apurata' => [
            'compra' => $result
            ]]);
        return $response;
    }

    /**
     * Return response for bad request
     * @param ResultInterface $response
     * @return ResultInterface
     */
    private function processBadRequest(ResultInterface $response)
    {
        $response->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);
        $response->setData(['message' => __('Sorry, but something went wrong')]);

        return $response;
    }
}
