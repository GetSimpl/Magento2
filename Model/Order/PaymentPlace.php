<?php
namespace Simpl\Splitpay\Model\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Info;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\ManagerInterface;
use Magento\Sales\Api\CreditmemoManagementInterface as CreditmemoManager;
use Magento\Sales\Model\Order\Payment;

class PaymentPlace extends Payment
{
    /**
     * @return $this|PaymentPlace
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function place()
    {
        $this->_eventManager->dispatch('sales_order_payment_place_start', ['payment' => $this]);
        $order = $this->getOrder();

        $order->setCustomerNoteNotify(false);

        $this->setAmountOrdered($order->getTotalDue());
        $this->setBaseAmountOrdered($order->getBaseTotalDue());
        $this->setShippingAmount($order->getShippingAmount());
        $this->setBaseShippingAmount($order->getBaseShippingAmount());

        $methodInstance = $this->getMethodInstance();
        $methodInstance->setStore($order->getStoreId());

        $orderState = Order::STATE_NEW;
        $orderStatus = $methodInstance->getConfigData('order_status');
        $isCustomerNotified = $order->getCustomerNoteNotify();

        $methodInstance->validate();
        $action = $methodInstance->getConfigPaymentAction();

        if ($action) {
            if ($methodInstance->isInitializeNeeded()) {
                $stateObject = new \Magento\Framework\DataObject();
                $methodInstance->initialize($methodInstance->getConfigData('payment_action'), $stateObject);
                $orderState = $stateObject->getData('state') ?: $orderState;
                $orderStatus = $stateObject->getData('status') ?: $orderStatus;
                $isCustomerNotified = $stateObject->hasData('is_notified')
                    ? $stateObject->getData('is_notified')
                    : $isCustomerNotified;
            } else {
                $orderState = Order::STATE_PROCESSING;
                $this->processAction($action, $order);
                $orderState = $order->getState() ? $order->getState() : $orderState;
                $orderStatus = $order->getStatus() ? $order->getStatus() : $orderStatus;
            }
        } else {
            $order->setState($orderState)
                ->setStatus($orderStatus);
        }

        $isCustomerNotified = $isCustomerNotified ?: $order->getCustomerNoteNotify();

        if (!array_key_exists($orderStatus, $order->getConfig()->getStateStatuses($orderState))) {
            $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
        }

        $this->updateOrder($order, $orderState, $orderStatus, $isCustomerNotified);

        $this->_eventManager->dispatch('sales_order_payment_place_end', ['payment' => $this]);

        return $this;
    }
}
