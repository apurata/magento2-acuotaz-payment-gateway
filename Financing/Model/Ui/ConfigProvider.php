<?php

namespace Apurata\Financing\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Apurata\Financing\Gateway\Config\Config;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'apurata_financing';

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
                    'financingIntentUrl' => $this->config->getFinancingIntentUrl(),
                    'financingCreationUrl' => $this->config->getFinancingCreationUrl(),
                ],
            ]
        ];
    }
}
