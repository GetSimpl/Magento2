<?php
namespace Simpl\Splitpay\Model\Source;

class EnabledFor implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['value' => 1, 'label' => 'All Products'];
        $options[] = ['value' => 2, 'label' => 'Products without Special Price'];

        return $options;
    }
}
