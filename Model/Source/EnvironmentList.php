<?php
namespace Simpl\Splitpay\Model\Source;

class EnvironmentList implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['value' => 'production', 'label' => 'Production (Live mode)'];
        $options[] = ['value' => 'sandbox', 'label' => 'Sandbox (Test mode)'];
        return $options;
    }
}
