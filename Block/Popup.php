<?php
namespace Simpl\Splitpay\Block;

class Popup extends \Magento\Framework\View\Element\Template
{
    protected $config;
    protected $registry;
    protected $pricingHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Simpl\Splitpay\Model\Config $config,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->pricingHelper = $pricingHelper;
        parent::__construct($context);
    }

    public function getProduct()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return $product;
        } else {
            return false;
        }
    }

    public function getFormattedPrice($price = 0)
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    public function getInfoHtml($formattedPrice)
    {
        $description = $this->config->getPopupDescription($formattedPrice);
        return $description;
    }

    public function getEnabledFor()
    {
        return $this->config->getEnabledFor();
    }

    public function getMinPriceConfig()
    {
        return !empty($this->config->getConfigData('min_price_limit')) ? $this->config->getConfigData('min_price_limit') : '';
    }
    public function getMaxPriceValue()
    {
        return 25000;
    }
}
