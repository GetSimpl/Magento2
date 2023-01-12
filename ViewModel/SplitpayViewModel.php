<?php

namespace Simpl\Splitpay\ViewModel;

use Magento\Framework\View\Element\Context;

class SplitpayViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    public $coreSession;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Simpl\Splitpay\Model\Config
     */
    public $simplConfig;

    /**
     * @param Context $context
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Simpl\Splitpay\Model\Config $simplConfig
     */
    public function __construct(
        Context                                            $context,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Store\Model\StoreManagerInterface         $storeManager,
        \Simpl\Splitpay\Model\Config                       $simplConfig
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        $this->coreSession = $coreSession;
        $this->storeManager = $storeManager;
        $this->simplConfig = $simplConfig;
    }

    /**
     * @return \Simpl\Splitpay\Model\Config
     */
    public function getSimplConfigData()
    {
        return $this->simplConfig;
    }


}
