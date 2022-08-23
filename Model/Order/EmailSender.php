<?php
namespace Simpl\Splitpay\Model\Order;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;

class EmailSender extends OrderSender
{
    protected $methodCode = \Simpl\Splitpay\Model\PaymentMethod::METHOD_CODE;

    protected $logger;
    protected $orderResource;
    protected $globalConfig;

    public function __construct(
        Template $templateContainer,
        OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderResource $orderResource,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );

        $this->logger = $logger;
        $this->orderResource = $orderResource;
        $this->globalConfig = $globalConfig;
    }

    public function send(Order $order, $forceSyncMode = false, $flag = false)
    {
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        $payment_confirmation_mail = 1;
        if ($payment == $this->methodCode && !$flag && $payment_confirmation_mail) {
            return false;
        }
        $order->setSendEmail(true);

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            if ($this->checkAndSend($order)) {
                $order->setEmailSent(true);
                $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $order->setEmailSent(null);
            $this->orderResource->saveAttribute($order, 'email_sent');
        }

        $this->orderResource->saveAttribute($order, 'send_email');

        return false;
    }
}
