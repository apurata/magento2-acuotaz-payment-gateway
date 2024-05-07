<?php

namespace Apurata\Financing\Block;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\RequestBuilder;
use Apurata\Financing\Model\Financing;
use Exception;

class Addon extends \Magento\Framework\View\Element\Template
{
    private $apurata_script = null;
    public function __construct(
        private \Magento\Framework\View\Element\Template\Context $context,
        private \Magento\Framework\UrlInterface $urlBuilder,
        private \Magento\Checkout\Model\SessionFactory $session,
        private \Magento\Customer\Model\SessionFactory $customerSession,
        private \Magento\Customer\Model\Session $customerSession2,
        private \Magento\Framework\Registry $registry,
        private RequestBuilder $requestBuilder,
        private Financing $financing,
        private array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    private function getSessionId()
    {
        $sessionId = '';
        try {
            $sessionId = $this->customerSession2->getApurataId() ?: $this->customerSession2->getSessionId();
            $this->customerSession2->setApurataId($sessionId);
        } catch (\Throwable $e) {
            error_log('Apurata Log: can not get session_id');
        }
        return $sessionId;
    }

    private function getUrlParams($page, $cart, $product)
    {
        $current_user = $this->customerSession->create()->getCustomer();
        $urlParams = [
            'page' => $page,
            'continue_url' => $this->urlBuilder->getCurrentUrl(),
            'multiple_products' => ($page === 'cart' && $cart->getItemsQty() > 1) ? 'TRUE' : null,
            'product__id' => $product ? $product->getId() : null,
            'product__name' => $product ? $product->getName() : null,
            'user__id' => $current_user ? $current_user->getId() : null,
            'user__email' => $current_user ? $current_user->getEmail() : null,
            'user__first_name' => $current_user ? $current_user->getName() : null,
            'user__last_name' => $current_user ? $current_user->getLastname() : null,
            'user__session_id' => $this->getSessionId(),
        ];
        return array_filter($urlParams, fn ($value) => $value !== null);
    }

    private function getApurataAddonInsecure($page)
    {
        if (!$this->financing->isAvailable()) {
            return '';
        }
        $cart = $this->session->create()->getQuote();
        $product = $this->getCurrentProduct();
        $total = ($page == 'product' && $product) ? $product->getFinalPrice() : $cart->getGrandTotal();
        $urlParams = $this->getUrlParams($page, $cart, $product);
        $url = ConfigData::APURATA_ADD_ON . urlencode($total) . '?' . http_build_query(array_map('urlencode', $urlParams));
        list($respCode, $payWithApurataAddon) = $this->requestBuilder->makeCurlToApurata("GET", $url);
        $addon = ($respCode == 200) ? str_replace(array("\r", "\n"), '', $payWithApurataAddon) : '';
        return $addon;
    }
    public function getApurataAddon($page)
    {
        try {
            return $this->getApurataAddonInsecure($page);
        } catch (\Throwable $e) {
            error_log(sprintf(
                "Apurata log: %s in file : %s line: %s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
        }
        return '';
    }

    public function getApurataPixel()
    {
        $url = '/pos/apurata-pixel';
        $apurata_script = null;
        if ($this->apurata_script) {
            return $this->apurata_script;
        }
        list($respCode, $apurata_script) = $this->requestBuilder->makeCurlToApurata("GET", $url);
        if ($respCode == 200) {
            $this->apurata_script = $apurata_script;
            return $this->apurata_script;
        } else {
            error_log(sprintf('Apurata responded with http_code %s', $respCode));
            return '';
        }
    }
}
