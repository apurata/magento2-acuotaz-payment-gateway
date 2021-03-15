<?php
namespace Apurata\Financing\Block;

class ThankYou extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\SessionFactory $session,
        \Magento\Customer\Model\SessionFactory $customerSession,
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
	}

	public function actionUrl($action)
	{
		return __($this->_urlBuilder->getUrl($action));
    }

    public function getOrder()
    {
        $checkout = $this->session->create();
        return $checkout->getLastRealOrder();
        /* if (!$this->hasData('orderId')) {
            $this->setData('orderId', $checkout->getLastRealOrder()->getIncrementId());
        }
        return __($this->_getData('orderId')); */
    }

    public function getCustomerId()
    {
        $customer = $this->customerSession->create();
        return __($customer->getCustomer()->getId());
    }
}