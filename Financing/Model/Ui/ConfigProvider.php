<?php

namespace Apurata\Financing\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Apurata\Financing\Gateway\Config\Config;
use Apurata\Financing\Helper\ConfigData;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = ConfigData::PAYMENT_CODE;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'apurataClientId' => $this->config->getApurataClientId(),
                    'financingIntentUrl' => $this->config->getFinancingIntentUrl(),
                    'financingCreationUrl' => ConfigData::APURATA_DOMAIN.ConfigData::APURATA_CREATE_ORDER_URL,
                ],
            ]
        ];
    }
}
