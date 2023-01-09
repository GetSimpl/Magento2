<?php
namespace Simpl\Splitpay\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Request extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Simpl\Splitpay\Model\Config
     */
    protected $config;
    /**
     * @var \Simpl\Splitpay\Model\Airbreak
     */
    protected $airbreak;
    /**
     * @var
     */
    protected $_helper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Simpl\Splitpay\Model\Config $config
     * @param \Simpl\Splitpay\Model\Airbreak $airbreak
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Simpl\Splitpay\Model\Config $config,
        \Simpl\Splitpay\Model\Airbreak $airbreak,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct(
            $context
        );

        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->airbreak = $airbreak;
        $this->orderFactory = $orderFactory;
        $this->_messageManager = $messageManager;
        $this->curl = $curl;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

            if ($this->checkoutSession->getLastRealOrderId()) {
                $order = $this->orderFactory->create()->loadByIncrementId($this->checkoutSession->getLastRealOrderId());

                $paymentMethod = $order->getPayment()->getMethodInstance();
                $order->setStatus($paymentMethod->getConfigData('new_order_status'));
                $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
                $order->addStatusHistoryComment('Redirected to Simpl', false);
                $order->save();

                $streetBillingAddress = $order->getBillingAddress()->getStreet();
                $streetShippingAddress = $order->getShippingAddress()->getStreet();
                $requestParam = [
                    'merchant_client_id' => $this->config->getClientId(),
                    'transaction_status_redirection_url' => $this->_url->getUrl('splitpay/payment/response'),
                    'order_id' => $this->checkoutSession->getLastRealOrderId(),
                    'amount_in_paise' => (int) (round($order->getGrandTotal(), 2) * 100),
                    'user' => [
                        'phone_number' => preg_replace('/[^0-9]/', '',$order->getBillingAddress()->getTelephone()),
                        'email' => $order->getCustomerEmail(),
                        'first_name' => $order->getBillingAddress()->getFirstname(),
                        'last_name' => $order->getBillingAddress()->getLastname()
                    ],
                    'billing_address' => [
                        'line1' => $streetBillingAddress[0],
                        'line2' => isset($streetBillingAddress[1])?$streetBillingAddress[1]:'',
                        'city' => $order->getBillingAddress()->getCity(),
                        'pincode' => $order->getBillingAddress()->getPostcode()
                    ],
                    'shipping_address' => [
                        'line1' => $streetShippingAddress[0],
                        'line2' => isset($streetShippingAddress[1])?$streetShippingAddress[1]:'',
                        'city' => $order->getShippingAddress()->getCity(),
                        'pincode' => $order->getShippingAddress()->getPostcode()
                    ],
                ];

                $itemArr = [];
                $items = $order->getAllVisibleItems();
                foreach ($items as $item) {
                    $itemArr[] = [
                        'quantity' => (int) $item->getQtyOrdered(),
                        'rate_per_item' => (int) (round($item->getPriceInclTax(), 2) * 100),
                        'sku' => $item->getSku()
                    ];
                }

                $requestParam['items'] = $itemArr;
                $testMode = $this->config->isTestMode();
                if ($testMode != 'production') {
                    $requestParam['mock_eligibility_response'] = 'eligibility_success';
                    $requestParam['mock_eligibility_amount_in_paise'] = 500000;
                }

                $requestParam['transaction_status_webhook_url'] = $this->_url->getUrl('splitpay/payment/webhook');

                $url = $this->config->getApiDomain().'/api/v1/transaction/initiate';

                $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
                $this->curl->setOption(CURLOPT_TIMEOUT, 0);
                $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
                $this->curl->addHeader("Content-Type", "application/json");
                $this->curl->addHeader("Authorization", $this->config->getClientKey());
                $this->curl->post($url, json_encode($requestParam));
                $response = json_decode($this->curl->getBody(), true);
                if ($response['success']) {
                    $resultRedirect->setUrl($response['data']['redirection_url']);
                } else if(isset($response['error'])){{
                    $messageParse = 'Sorry, there was a problem preparing your payment.';
                    $backTrace = array('file'=>__FILE__,'line'=>__LINE__,'error'=>$response['error']);
                    $this->airbreak->sendCustomAirbreakAlert($messageParse,$backTrace, $this->checkoutSession->getLastRealOrderId());
                    throw new \Magento\Framework\Exception\LocalizedException(__($response['error']['message']));
                }
            }
        } catch (\Exception $e) {
            $this->_messageManager->addError($e->getMessage());
            $this->airbreak->sendData($e, []);
            $resultRedirect->setpath('checkout/cart');
        }

        return $resultRedirect;
    }
}
