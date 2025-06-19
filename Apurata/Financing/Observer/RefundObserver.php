<?php

namespace Apurata\Financing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Apurata\Financing\Helper\RequestBuilder;
use Apurata\Financing\Helper\ErrorHandler;

class RefundObserver implements ObserverInterface
{
    public function __construct(
        private RequestBuilder $requestBuilder,
        private ManagerInterface $messageManager,
        private ErrorHandler $errorHandler
    ) {}

    private function makeRefundInsecure($observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $orderId = $order->getId();
        $refundAmount = $creditmemo->getGrandTotal();  // Amount to refund
        $totalRefunded = $order->getTotalRefunded();  // Amount already refunded, includes the current refund
        $orderTotal = $order->getGrandTotal();
        $extra_headers = ['X-Unique-Token:' . bin2hex(random_bytes(16))];
        $data = [
            'reason' => $totalRefunded  == $orderTotal
                ? 'MAGENTO2 API, Total Refund'
                : 'MAGENTO2 API, Partial Refund',
            'author' => 'Merchant dashboard'
        ];
        if ($totalRefunded  == $orderTotal) {
            $url = "/pos/order/{$orderId}/total-refund";
        } else {
            $data['amount'] = $refundAmount;
            $url = "/pos/order/{$orderId}/partial-refund";
        }
        $apiResult = $this->requestBuilder->makeCurlToApurata("POST", $url, $data, false, $extra_headers);
        if ($apiResult['http_code'] != 200) {
            throw new \Exception('Error: ' . $apiResult['http_code'] . ' - Refund request failed for order ID ' . $orderId);
        }
    }

    public function execute(Observer $observer)
    {
        return $this->errorHandler->neverRaise(fn() => $this->makeRefundInsecure($observer), '');
    }
}
