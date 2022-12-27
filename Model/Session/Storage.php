<?php

namespace Simpl\Splitpay\Model\Session;

class Storage extends \Magento\Framework\Session\Storage
{

    protected $storeManager;
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
                                                   $namespace = 'simpl',
        array                                      $data = []
    )
    {
        parent::__construct($namespace, $data);
        $this->storeManager = $storeManager;
    }
}
