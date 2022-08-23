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
            if ($signature == $hash) {
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
                        } else {
                            $errorMsg = $response['error']['message'];
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
