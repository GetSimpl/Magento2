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

    protected $configSplitpay;

    /**
     * PaymentMethodAvailable constructor.
     * @param Cart $cart
     */
    public function __construct(Cart $cart, Config $configSplitpay)
    {
        $this->cart = $cart;
        $this->configSplitpay = $configSplitpay;
    }

    /**
     * payment_method_is_active event handler.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $paymentMethod = $observer->getEvent()->getMethodInstance()->getCode();
        $cartFinalAmount = $this->cart->getQuote()->getGrandTotal();

        if ($paymentMethod == "splitpay") {
            $minPrice = $this->configSplitpay->getMinPriceConfig();
            $maxPrice = $this->configSplitpay->getMaxPriceValue();
            $checkResult = $observer->getEvent()->getResult();
            if ($cartFinalAmount >= $minPrice && $cartFinalAmount <= $maxPrice) {
                $checkResult->setData('is_available', true);
            } else {
                $checkResult->setData('is_available', false);
            }
        }
    }
}
