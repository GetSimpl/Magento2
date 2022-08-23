<?php

namespace Simpl\Splitpay\Model;

use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Invoice;

class PaymentMethod extends AbstractMethod
{

    const METHOD_CODE = 'splitpay';
    const CURRENCY = 'INR';

    protected $_code = self::METHOD_CODE;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapturePartial = true;
    protected $config;
    protected $_logger;

    // Operationals params
    protected $orderSender;
    protected $invoiceService;
    protected $transaction;
    protected $transactionbuilder;
    protected $curl;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        \Magento\Payment\Model\Method\Logger                    $logger,
        \Simpl\Splitpay\Model\Config                            $config,

        \Magento\Sales\Model\Order\Email\Sender\OrderSender     $orderSender,
        \Magento\Sales\Model\Service\InvoiceService             $invoiceService,
        \Magento\Framework\DB\Transaction                       $transaction,
        \Magento\Framework\HTTP\Client\Curl                     $curl,
        \Magento\Sales\Model\Order\Payment\Transaction\Builder  $transactionbuilder,

        \Magento\Framework\App\RequestInterface                 $request,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [])
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->config = $config;

        $this->orderSender = $orderSender;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->transactionbuilder = $transactionbuilder;
        $this->curl = $curl;

    }

    /**
     * @param $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return ($currencyCode == self::CURRENCY);
    }

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        $result = parent::isAvailable($quote);
        if ($this->config->getEnabledFor() == 2 && $this->config->validateCartItems() == 0) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * @param InfoInterface $payment
     * @param $amount
     * @return $this|PaymentMethod
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $captureTxnId = $payment->getParentTransactionId();
        if ($captureTxnId) {
            $order = $payment->getOrder();
            $canRefundMore = $payment->getCreditmemo()->getInvoice()->canRefund();

            $requestParam = [
                'merchant_client_id' => $this->config->getClientId(),
                'amount_in_paise' => (int)(round($amount, 2) * 100),
                'transaction_id' => str_replace("-refund", "", $payment->getTransactionId()),
                'reason' => 'refund',
                'order_id' => "refund-" . $order->getIncrementId()
            ];

            $url = $this->config->getApiDomain() . '/api/v1/transaction/refund';

            $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", $this->config->getClientKey());
            $this->curl->post($url, json_encode($requestParam));
            $response = json_decode($this->curl->getBody(), true);

            if ($response['success'] == 1) {
                $payment->setTransactionId($response['data']['refunded_transaction_id'])
                    ->setIsTransactionClosed(1)
                    ->setShouldCloseParentTransaction(!$canRefundMore);
            } else {
                throw new LocalizedException(
                    __("Simpl gateway error code : " . $response['error']['code'])
                );
            }
        } else {
            throw new LocalizedException(
                __('We can\'t issue a refund transaction because there is no capture transaction.')
            );
        }
        return $this;
    }

    public function postProcessing(
        Order      $order,
        DataObject $payment,
                   $response
    )
    {

        $payment->setStatus(self::STATUS_APPROVED)
            ->setAmountPaid($order->getGrandTotal())
            ->setLastTransId($response['transaction_id'])
            ->setTransactionId($response['transaction_id'])
            ->setIsTransactionClosed(false)
            ->setShouldCloseParentTransaction(false);

        $transaction = $this->transactionbuilder
            ->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($response['transaction_id'])
            ->setAdditionalInformation(
                [
                    Transaction::RAW_DETAILS => (array)$payment->getAdditionalInformation()
                ]
            )
            ->setFailSafe(true)
            ->build(Transaction::TYPE_CAPTURE);

        $transaction->save();
        $payment->save();

        $this->sendOrderMail($order);
        $this->createInvoice($order);
    }

    /**
     * @param $order
     * @return void
     */
    public function sendOrderMail($order)
    {
        $this->orderSender->send($order, false, true);
    }

    /**
     * @param $order
     * @return void
     * @throws LocalizedException
     */
    protected function createInvoice($order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $invoice->save();
            $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();

            $order->setState(Order::STATE_PROCESSING, true);
            $order->setStatus($this->getConfigData('payment_success_order_status'));
            $order->save();

            $order->addStatusHistoryComment('Automatically INVOICED.', false)->save();
        }
    }
}
