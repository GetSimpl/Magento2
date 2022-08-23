<?php
namespace Simpl\Splitpay\Model\Observer;

class EventTrack implements \Magento\Framework\Event\ObserverInterface
{
    protected $eventTrack;
    protected $session;
    protected $registry;
    protected $cart;
    
    public function __construct(
        \Simpl\Splitpay\Model\EventTrack $eventTrack,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->eventTrack = $eventTrack;
        $this->session = $session;
        $this->registry = $registry;
        $this->cart = $cart;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
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
        }
        
        if ($action == 'checkout_index_index') {
            $cart = $this->cart;
            $data = [
                'user_id' => (int)$customerId,
                'order_amount' => $cart->getQuote()->getGrandTotal(),
                'order_id' => $cart->getQuote()->getId()
            ];
            $this->eventTrack->sendData('CHECKOUT_PAGE_VIEW', $data);
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
    }
}
