<?php
namespace Simpl\Splitpay\Block;

class Popup extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Simpl\Splitpay\Model\Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Simpl\Splitpay\Model\Config $config
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     */
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

    /**
     * @return false|mixed
     */
    public function getProduct()
    {
        $product = $this->registry->registry('current_product');
        if ($product) {
            return $product;
        } else {
            return false;
        }
    }

    /**
     * @param $price
     * @return float|string
     */
    public function getFormattedPrice($price = 0)
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * @param $formattedPrice
     * @return array|string|string[]
     */
    public function getInfoHtml($formattedPrice)
    {
        $description = $this->config->getPopupDescription($formattedPrice);
        return $description;
    }

    /**
     * @return mixed
     */
    public function getEnabledFor()
    {
        return $this->config->getEnabledFor();
    }

    /**
     * @return mixed|string
     */
    public function getMinPriceConfig()
    {
        return !empty($this->config->getConfigData('min_price_limit')) ? $this->config->getConfigData('min_price_limit') : '';
    }

    /**
     * @return int
     */
    public function getMaxPriceValue()
    {
        return 25000;
    }
}
