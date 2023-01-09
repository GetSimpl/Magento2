<?php

namespace Simpl\Splitpay\Model;

class EventTrack
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $session;
    /**
     * @var \Magento\Framework\HTTP\Header
     */
    protected $httpHeader;
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $remoteIp;
    /**
     * @var Airbreak
     */
    protected $airbreak;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @param Config $config
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Framework\HTTP\Header $httpHeader
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp
     * @param Airbreak $airbreak
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Simpl\Splitpay\Model\Config                         $config,
        \Magento\Framework\Session\SessionManagerInterface   $session,
        \Magento\Framework\HTTP\Header                       $httpHeader,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteIp,
        \Simpl\Splitpay\Model\Airbreak                       $airbreak,
        \Magento\Framework\HTTP\Client\Curl                  $curl
    )
    {
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

    /**
     * @return void
     */
    protected function setSessionValue()
    {
        $this->session->start();
        $this->session->setEventSessionId(uniqid('magesimpl'));
    }

    /**
     * @return mixed
     */
    protected function getSessionValue()
    {
        $this->session->start();
        return $this->session->getEventSessionId();
    }

    /**
     * @param $action
     * @param $data
     * @return void
     */
    public function sendData($action, $data = [])
    {
        try {
            $url = $this->config->getApiDomain() . '/api/v1/plugins/notify';

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
