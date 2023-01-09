<?php
namespace Simpl\Splitpay\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    protected $config;
    protected $_messageManager;
    protected $quoteManagement;
    protected $quote;
    protected $airbreak;

    /**
     * @return \Simpl\Splitpay\Model\Config
     */
    public function getConfig(): \Simpl\Splitpay\Model\Config
    {
        return $this->config;
    }

    /**
     * @param \Simpl\Splitpay\Model\Config $config
     */
    public function setConfig(\Simpl\Splitpay\Model\Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getMessageManager()
    {
        return $this->_messageManager;
    }

    /**
     * @param mixed $messageManager
     */
    public function setMessageManager($messageManager): void
    {
        $this->_messageManager = $messageManager;
    }

    /**
     * @return mixed
     */
    public function getQuoteManagement()
    {
        return $this->quoteManagement;
    }

    /**
     * @param mixed $quoteManagement
     */
    public function setQuoteManagement($quoteManagement): void
    {
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * @return mixed
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * @param mixed $quote
     */
    public function setQuote($quote): void
    {
        $this->quote = $quote;
    }

    /**
     * @return \Simpl\Splitpay\Model\Airbreak
     */
    public function getAirbreak(): \Simpl\Splitpay\Model\Airbreak
    {
        return $this->airbreak;
    }

    /**
     * @param \Simpl\Splitpay\Model\Airbreak $airbreak
     */
    public function setAirbreak(\Simpl\Splitpay\Model\Airbreak $airbreak): void
    {
        $this->airbreak = $airbreak;
    }

    /**
     * @return \Magento\Sales\Model\OrderFactory
     */
    public function getOrderRepository(): \Magento\Sales\Model\OrderFactory
    {
        return $this->orderRepository;
    }

    /**
     * @param \Magento\Sales\Model\OrderFactory $orderRepository
     */
    public function setOrderRepository(\Magento\Sales\Model\OrderFactory $orderRepository): void
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function getCurl(): \Magento\Framework\HTTP\Client\Curl
    {
        return $this->curl;
    }

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function setCurl(\Magento\Framework\HTTP\Client\Curl $curl): void
    {
        $this->curl = $curl;
    }
    protected $orderRepository;
    protected $curl;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Simpl\Splitpay\Model\Config $config,
        \Simpl\Splitpay\Model\Airbreak $airbreak,
        \Magento\Sales\Model\OrderFactory $orderRepository,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct(
            $context
        );

        $this->config = $config;
        $this->airbreak = $airbreak;
        $this->orderRepository = $orderRepository;
        $this->curl = $curl;
    }

    public function execute()
    {
        try {
            $param = $this->getRequest()->getParams();
            ksort($param);
            $signature = $param['signature'];
            unset($param['signature']);
            $signature_algorithm = explode("-", $param['signature_algorithm']);
            unset($param['signature_algorithm']);
            $param = array_map(function ($v) {
                return urlencode($v);
            }, $param);
            $hash = hash_hmac(
                strtolower($signature_algorithm[1]),
                http_build_query($param),
                $this->config->getClientKey()
            );
            if ($signature == $hash && isset($param['order_id'])) {
                $orderId = $param['order_id'];
                $order = $this->orderRepository->create()->loadByIncrementId($orderId);

                if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {
                    if ($param['status'] == 'FAILED') {
                        $msg = 'Customer can not proceed with payment. so Simpl gateway cancel the order.';
                        $order->registerCancellation($msg);
                        $order->save();
                        return;
                    } elseif ($param['status'] == 'SUCCESS') {
                        $domain = $this->config->getApiDomain();
                        $url = $domain.'/api/v1/transaction_by_order_id/'.$param['order_id'].'/status';
                        $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
                        $this->curl->setOption(CURLOPT_TIMEOUT, 0);
                        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                        $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                        $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
                        $this->curl->addHeader("Authorization", $this->config->getClientKey());
                        $this->curl->get($url);
                        $response = json_decode($this->curl->getBody(), true);
                        if ($response['success']) {
                            $payment = $order->getPayment();
                            $paymentMethod = $order->getPayment()->getMethodInstance();
                            $paymentMethod->postProcessing($order, $payment, $param);
                        } else if(isset($response['error'])){
                            $errorMsg = $response['error']['message'];
                            $messageParse = 'There is some error while updating order status.';
                            $backTrace = array('file'=>__FILE__,'line'=>__LINE__,'error'=>$response['error']);
                            $this->airbreak->sendCustomAirbreakAlert($messageParse,$backTrace, $param['order_id']);
                            throw new \Magento\Framework\Exception\LocalizedException(__($errorMsg));
                        }

                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->airbreak->sendData($e, []);
            return;
        }
    }
}
