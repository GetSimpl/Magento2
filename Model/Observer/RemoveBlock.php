<?php
namespace Simpl\Splitpay\Model\Observer;

class RemoveBlock implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Simpl\Splitpay\Model\Config
     */
    protected $config;

    /**
     * @param \Simpl\Splitpay\Model\Config $config
     */
    public function __construct(
        \Simpl\Splitpay\Model\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getLayout();
        $productpage_block = $layout->getBlock('splitpay.msg.productpage');

        if ($productpage_block) {
            $remove = $this->config->isEnableAtProductPage();
            if ($remove == 0) {
                $layout->unsetElement('splitpay.msg.productpage');
            }
        }

        $catpage_block = $layout->getBlock('splitpay.msg.cartpage');

        if ($catpage_block) {
            $remove = $this->config->isEnableAtCartPage();
            if ($remove == 0) {
                $layout->unsetElement('splitpay.msg.cartpage');
            }
        }

        if ($this->config->isActive() == 0) {
            $layout->unsetElement('splitpay.msg.productpage');
            $layout->unsetElement('splitpay.msg.cartpage');
        }
    }
}
