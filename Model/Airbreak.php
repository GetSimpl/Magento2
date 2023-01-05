<?php

namespace Simpl\Splitpay\Model;

class Airbreak
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;
    /**
     * @var \Simpl\Splitpay\Logger\Logger
     */
    protected $simplLogger;

    /**
     * @param Config $config
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Simpl\Splitpay\Logger\Logger $logger
     */
    public function __construct(
        \Simpl\Splitpay\Model\Config        $config,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Simpl\Splitpay\Logger\Logger       $logger
    )
    {
        $this->config = $config;
        $this->curl = $curl;
        $this->simplLogger = $logger;
    }

    /**
     * @param $exception
     * @param $data
     * @return bool
     */
    public function sendData($exception, $data = [])
    {

        try {
            $order_id = null;
            $error_class = get_class($exception);
            $message = $exception->getMessage();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $trace = $exception->getTrace();

            if (isset($data['order_id'])) {
                $order_id = $data['order_id'];
            }

            $body = [];

            $body['context'] = [
                'environment' => $this->config->isTestMode(),
                'context' => 'error'
            ];

            $body['params'] = [
                'merchant_client_id' => $this->config->getClientId(),
                'order_id' => $order_id
            ];

            $body['errors'] = [];

            $code = [];
            foreach ($trace as $index => $trace_line) {
                $code[$index] = json_encode($trace_line);
            }

            $backtrace_data = [
                'file' => $file,
                'line' => $line,
                'code' => (object)$code
            ];

            $error_data = [
                'type' => $error_class,
                'message' => $message,
                'backtrace' => []
            ];
            array_push($error_data['backtrace'], $backtrace_data);
            array_push($body['errors'], $error_data);


            $airBreakEnabled = (bool)(int)$this->config->getConfigData('airbreakintegration');
            if (!$airBreakEnabled) {
                $this->simplLogger->info(json_encode($body));
                return true;
            }
            $airBreakProjectId = '337081';
            $airBreakProjectKey = '7369135ff3bb9c93250b4f90f47b3050';

            $url = 'https://api.airbrake.io/api/v3/projects/' . $airBreakProjectId . '/notices?key=' . $airBreakProjectKey;
            $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
            $this->curl->addHeader("Content-Type", "application/json");
            $this->curl->post($url, json_encode($body));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
