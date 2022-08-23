<?php

namespace Simpl\Splitpay\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Simpl\Splitpay\Model\Config;

class AddProductLayoutUpdateHandleObserver implements ObserverInterface
{
    const LAYOUT_HANDLE_NAME = 'catalog_product_view_custom_layout';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $event = $observer->getEvent();
        $actionName = $event->getData('full_action_name');
        if ($actionName === 'catalog_product_view') {
            $layout = $event->getData('layout');
            $layoutUpdate = $layout->getUpdate();

            $remove = $this->config->isEnableAtProductPage();
            if ($remove != 0) {
                $layoutUpdate->addHandle(static::LAYOUT_HANDLE_NAME);
            }
        }
    }
}
