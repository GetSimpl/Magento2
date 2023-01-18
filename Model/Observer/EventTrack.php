<?php
namespace Simpl\Splitpay\Model\Observer;

class EventTrack implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Simpl\Splitpay\Model\EventTrack
     */
    protected $eventTrack;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $coreSession;

    /**
     * @param \Simpl\Splitpay\Model\EventTrack $eventTrack
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     */
    public function __construct(
        \Simpl\Splitpay\Model\EventTrack $eventTrack,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    ) {
        $this->eventTrack = $eventTrack;
        $this->session = $session;
        $this->registry = $registry;
        $this->cart = $cart;
        $this->coreSession = $coreSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $currentPage = '';
        if ($event->getName() == 'controller_action_postdispatch') {
            $action = $observer->getRequest()->getFullActionName();
        } elseif ($event->getName() == 'checkout_cart_product_add_after') {
            $action = 'checkout_cart_product_add_after';
        } elseif ($event->getName() == 'checkout_submit_all_after') {
            $action = 'checkout_submit_all_after';
        }

        $customerSession = $this->session;
        $customerId = null;
        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
        }

        if ($action == 'catalog_product_view') {
            $product = $this->registry->registry('current_product');
            $data = [
                'user_id' => (int)$customerId,
                'product_id' => $product->getId(),
                'product_price' => $product->getFinalPrice()
            ];
            $this->eventTrack->sendData('PRODUCT_PAGE_VIEW', $data);
            $currentPage = 'product';
        }

        if ($action == 'checkout_cart_product_add_after') {
            $item = $observer->getEvent()->getData('quote_item');
            $item = ( $item->getParentItem() ? $item->getParentItem() : $item );

            $data = [
                'user_id' => (int)$customerId,
                'product_id' => $item->getProductId(),
                'product_price' => $item->getProduct()->getFinalPrice(),
                'quantity' => $item->getQty()
            ];
            $this->eventTrack->sendData('PRODUCT_ADD_TO_CART_CLICK', $data);
        }

        if ($action == 'checkout_cart_index') {
            $cart = $this->cart;
            $data = [
                'user_id' => (int)$customerId,
                'cart_amount_value' => $cart->getQuote()->getGrandTotal(),
                'cart_item_count' => (int)$cart->getQuote()->getItemsQty()
            ];
            $this->eventTrack->sendData('CART_PAGE_VIEW', $data);
            $currentPage = 'cart';
        }

        if ($action == 'checkout_index_index') {
            $cart = $this->cart;
            $data = [
                'user_id' => (int)$customerId,
                'order_amount' => $cart->getQuote()->getGrandTotal(),
                'order_id' => $cart->getQuote()->getId()
            ];
            $this->eventTrack->sendData('CHECKOUT_PAGE_VIEW', $data);
            $currentPage = 'checkout';
        }

        if ($action == 'checkout_submit_all_after') {
            $order = $observer->getOrder();
            $data = [
                'user_id' => (int)$customerId,
                'order_amount' => $order->getGrandTotal(),
                'payment_method' => $order->getPayment()->getMethodInstance()->getTitle()
            ];
            $this->eventTrack->sendData('CHECKOUT_PLACE_ORDER_CLICK', $data);
        }
        $this->coreSession->setData('currentPage',$currentPage);
    }
}
