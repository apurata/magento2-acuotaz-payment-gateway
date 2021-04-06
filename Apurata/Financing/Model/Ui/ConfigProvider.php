<?php

namespace Apurata\Financing\Model\Ui;

use Apurata\Financing\Helper\ConfigData;
use Apurata\Financing\Helper\ConfigReader;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = ConfigData::PAYMENT_CODE;

    public function __construct(
        ConfigReader $configReader,
        UrlInterface $urlHelper
    ) {
        $this->configReader = $configReader;
        $this->urlHelper = $urlHelper;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->configReader->getIsActive(),
                    'apurataClientId' => $this->configReader->getClientId(),
                    'financingIntentUrl' => $this->urlHelper->getUrl(ConfigData::FINANCING_INTENT_PATH),
                    'financingAddOnUrl' => $this->urlHelper->getUrl(ConfigData::FINANCING_ADD_ON_PATH),
                    'financingCreationUrl' => ConfigData::APURATA_DOMAIN.ConfigData::APURATA_CREATE_ORDER_URL
                ],
            ]
        ];
    }
}
