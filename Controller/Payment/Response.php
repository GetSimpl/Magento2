<?php
namespace Simpl\Splitpay\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Response extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Simpl\Splitpay\Model\Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Simpl\Splitpay\Model\Airbreak
     */
    protected $airbreak;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderRepository;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Simpl\Splitpay\Model\Config $config
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Simpl\Splitpay\Model\Airbreak $airbreak
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderRepository
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Simpl\Splitpay\Model\Config $config,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Simpl\Splitpay\Model\Airbreak $airbreak,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderRepository,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct(
            $context
        );

        $this->config = $config;
        $this->_messageManager = $messageManager;
        $this->airbreak = $airbreak;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->curl = $curl;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $baseUrl = $this->_url->getBaseUrl();
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

                if ($param['status'] == 'FAILED') {
                    $order->registerCancellation('Customer cancel transaction.')->save();
                    $this->_checkoutSession->restoreQuote();
                    $this->_messageManager->addErrorMessage(__('Order canceled.'));
                    $response = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    $response->setUrl($baseUrl.'checkout/cart');
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

                        $response = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                        $response->setUrl($baseUrl.'checkout/onepage/success');
                    } else {
                        throw new \Magento\Framework\Exception\LocalizedException(__($response['error']['message']));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_checkoutSession->restoreQuote();
            $this->airbreak->sendData($e, []);
            $this->_messageManager->addErrorMessage(__($e->getMessage()));
            $response = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $response->setUrl($baseUrl.'checkout/cart');
        }

        return $response;
    }
}
