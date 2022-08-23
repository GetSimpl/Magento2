<?php
namespace Simpl\Splitpay\Model;

class EventTrack
{
    protected $config;
    protected $session;
    protected $httpHeader;
    protected $remoteIp;
    protected $airbreak;
    protected $curl;
    
    public function __construct(
        \Simpl\Splitpay\Model\Config $config,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\HTTP\Header $httpHeader,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp,
        \Simpl\Splitpay\Model\Airbreak $airbreak,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->httpHeader = $httpHeader;
        $this->remoteIp = $remoteIp;
        if (empty($this->getSessionValue())) {
            $this->setSessionValue();
        }
        $this->airbreak = $airbreak;
        $this->curl = $curl;
    }
    
    protected function setSessionValue()
    {
        $this->session->start();
        $this->session->setEventSessionId(uniqid('magesimpl'));
    }
 
    protected function getSessionValue()
    {
        $this->session->start();
        return $this->session->getEventSessionId();
    }
    
    public function sendData($action, $data = [])
    {
        try {
            $url = $this->config->getApiDomain().'/api/v1/plugins/notify';
            
            $requestParam = [
                'plugin' => 'magento',
                'journey_id' => $this->getSessionValue(),
                'merchant_client_id' => $this->config->getClientId(),
                'device_params' => [
                    'user_agent' => $this->httpHeader->getHttpUserAgent(),
                    'ip_address' => $this->remoteIp->getRemoteAddress()
                ],
                'action' => $action,
                'payload' => $data
            ];

            $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->addHeader("Authorization", $this->config->getClientKey());
            $this->curl->post($url, json_encode($requestParam));
            $response = $this->curl->getBody();
            
        } catch (\Exception $e) {
            $this->airbreak->sendData($e, []);
        }
    }
}
