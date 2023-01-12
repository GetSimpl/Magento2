<?php

namespace Simpl\Splitpay\Setup\Patch\Schema;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Cloneconfig implements SchemaPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ScopeConfigInterface     $scopeConfig,
        EncryptorInterface       $encryptor
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    /**
     * @return Cloneconfig|void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /*Add your Clone config code*/

        // Previous versions configuration
        $oldToNewKey = [
            'payment/splitpay/active' => 'payment/simplpayin3/active',
            'payment/splitpay/airbreakintegration' => 'payment/simplpayin3/airbreakintegration',
            'payment/splitpay/cart_page_font_size' => 'payment/simplpayin3/cart_page_font_size',
            'payment/splitpay/cart_page_font_weight' => 'payment/simplpayin3/cart_page_font_weight',
            'payment/splitpay/checkout_page_font_size' => 'payment/simplpayin3/checkout_page_font_size',
            'payment/splitpay/checkout_page_font_weight' => 'payment/simplpayin3/checkout_page_font_weight',
            'payment/splitpay/client_id' => 'payment/simplpayin3/client_id',
            'payment/splitpay/client_key' => 'payment/simplpayin3/client_key',
            'payment/splitpay/description' => 'payment/simplpayin3/description',
            'payment/splitpay/enabled_for' => 'payment/simplpayin3/enabled_for',
            'payment/splitpay/enable_popup_cart_page' => 'payment/simplpayin3/enable_popup_cart_page',
            'payment/splitpay/enable_popup_checkout_page' => 'payment/simplpayin3/enable_popup_checkout_page',
            'payment/splitpay/enable_popup_product_page' => 'payment/simplpayin3/enable_popup_product_page',
            'payment/splitpay/max_price_limit' => 'payment/simplpayin3/max_price_limit',
            'payment/splitpay/min_price_limit' => 'payment/simplpayin3/min_price_limit',
            'payment/splitpay/new_order_status' => 'payment/simplpayin3/new_order_status',
            'payment/splitpay/payment_success_order_status' => 'payment/simplpayin3/payment_success_order_status',
            'payment/splitpay/popup' => 'payment/simplpayin3/popup',
            'payment/splitpay/product_page_font_size' => 'payment/simplpayin3/product_page_font_size',
            'payment/splitpay/product_page_font_weight' => 'payment/simplpayin3/product_page_font_weight',
            'payment/splitpay/sort_order' => 'payment/simplpayin3/sort_order',
            'payment/splitpay/test_mode' => 'payment/simplpayin3/test_mode',
            'payment/splitpay/title' => 'payment/simplpayin3/title'
        ];
        $this->copyConfig($oldToNewKey);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        // TODO: Implement getDependencies() method.
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        // TODO: Implement getAliases() method.
        return [];
    }

    /**
     * @param $paths
     * @return void
     */
    public function copyConfig($paths)
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configDataTable = $this->moduleDataSetup->getTable('core_config_data');

        foreach ($paths as $srcPath => $dstPath) {
            if ($srcPath == 'payment/splitpay/client_id' || $srcPath == 'payment/splitpay/client_key') {
                $oldValueEncrypt = $this->scopeConfig->getValue($srcPath);
                $valueDecrypt = $this->encryptor->decrypt($oldValueEncrypt);
                $value = $this->encryptor->encrypt($valueDecrypt);
            } else {
                $value = $this->scopeConfig->getValue($srcPath);
            }

            if (is_array($value)) {
                foreach (array_keys($value) as $v) {
                    $this->copyConfig([$srcPath . '/' . $v => $dstPath . '/' . $v]);
                }
            } else {

                $selectQuery = $connection->select()
                    ->from($configDataTable)
                    ->where('path = ?', $srcPath);

                $fetchRows = $connection->fetchAll($selectQuery);
                foreach ($fetchRows as $row) {
                    $row['path'] = $dstPath;
                    $oldValue = array('value' => $row['value']);

                    // check if new path entry already exists, then update the value with old value, else insert the new path
                    $selectQueryPath = $connection->select()
                        ->from($configDataTable, 'config_id')
                        ->where('path = ?', $dstPath);
                    $checkExists = $connection->fetchOne($selectQueryPath);

                    if ($checkExists) {
                        $connection->update($configDataTable, $oldValue, ['config_id=?' => $checkExists]);
                    } else {
                        unset($row['config_id']);
                        $connection->insert($configDataTable, $row);
                    }
                }
            }

        }

    }

}
