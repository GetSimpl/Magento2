<?php

namespace Simpl\Splitpay\Model\Observer;

use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Simpl\Splitpay\Model\Config;

class SplitpayCheckValidCartAmount implements ObserverInterface
{

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var Config
     */
    protected $configSplitpay;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * PaymentMethodAvailable constructor.
     * @param Cart $cart
     */
    public function __construct(Cart                                       $cart, Config $configSplitpay,
                                \Magento\Store\Model\StoreManagerInterface $storeManager)
    {
        $this->cart = $cart;
        $this->configSplitpay = $configSplitpay;
        $this->storeManager = $storeManager;
    }

    /**
     * payment_method_is_active event handler.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        $getCartData = $this->cart;
        $cartFinalAmount = $getCartData->getQuote()->getGrandTotal();

        if ($paymentMethod == $this->configSplitpay->getPaymentMethodCode()) {

            $checkResult = $observer->getEvent()->getResult();
            $billingCountry = $getCartData->getQuote()->getBillingAddress()->getCountryId();
            $validateSplitPayavability = $this->configSplitpay->checkCountryCurrencyCriteria($billingCountry);

            if (!$validateSplitPayavability) {
                return $checkResult->setData('is_available', false);
            }
            $minPrice = $this->configSplitpay->getMinPriceConfig();
            $maxPrice = $this->configSplitpay->getMaxPriceValue();

            if (empty($minPrice) && empty($maxPrice)) {
                return $checkResult->setData('is_available', true);
            } else if ($cartFinalAmount >= $minPrice && $cartFinalAmount <= $maxPrice) {
                return $checkResult->setData('is_available', true);
            } else {
                return $checkResult->setData('is_available', false);
            }
        }
    }
}
