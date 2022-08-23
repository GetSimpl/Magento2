<?php

namespace Simpl\Splitpay\Model;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    protected $config;

    public function __construct(
        \Simpl\Splitpay\Model\Config $config
    )
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        if (!$this->config->isActive()) {
            return [];
        }

        $config = [
            'payment' => [
                'splitpay' => [
                    'title' => $this->config->getTitle(),
                    'description' => $this->config->getDescription()
                ],
            ],
            'getsimple' => [
                'enablepopup' => $this->config->isEnableAtCheckoutPage(),
                'popupurl' => $this->config->getPopupUrl(),
                'popupdescription' => $this->config->getPopupDescription(),
                'enabledfor' => $this->config->getEnabledFor(),
                'isMethodAvailable' => $this->config->validateCartItems(),
                'minPriceLimit' => $this->config->getMinPriceConfig(),
                'maxPriceLimit' => $this->config->getMaxPriceValue()
            ]
        ];

        return $config;
    }
}
