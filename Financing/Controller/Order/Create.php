<?php

namespace Apurata\Financing\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartManagementInterface;

class Create extends Action
{
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        QuoteFactory $quoteFactory,
        CartManagementInterface $cartManagementInterface
    ) {
        parent::__construct($context);

        $this->quoteFactory = $quoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {      
        $quoteId = $this->getRequest()->getParam('quoteId');

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
        if($order->getEntityId()){
            $result['order_id']= $order->getRealOrderId();
        }else{
            $result=['error'=>1,'msg'=>'Your custom message'];
        }
        
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData(['financingIntent' => [
            'extra' => $result
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
