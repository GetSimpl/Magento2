<?php
namespace Simpl\Splitpay\Controller\Payment;

use Magento\Framework\Controller\ResultFactory;

class Event extends \Magento\Framework\App\Action\Action
{

    protected $eventTrack;
    protected $airbreak;
    protected $session;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Simpl\Splitpay\Model\EventTrack $eventTrack,
        \Simpl\Splitpay\Model\Airbreak $airbreak,
        \Magento\Customer\Model\Session $session
    ) {
        parent::__construct(
            $context
        );
        $this->eventTrack = $eventTrack;
        $this->airbreak = $airbreak;
        $this->session = $session;
    }

    public function execute()
    {
        try {
            $param = $this->getRequest()->getParams();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerSession = $this->session;
            $customerId = null;
            if ($customerSession->isLoggedIn()) {
                $customerId = $customerSession->getCustomer()->getId();
            }
            
            $action = $param['action'];
            unset($param['action']);
            $data = [
                'user_id' => $customerId
            ];
            $data = array_merge($data, $param);
            $this->eventTrack->sendData($action, $data);
            
            $responseContent = [
                'message' => __("event track"),
                'success' => 1
            ];
        } catch (\Exception $e) {
            $responseContent = [
                'message' => __($e->getMessage()),
                'success' => 0
            ];
            $this->airbreak->sendData($e, []);
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setData($responseContent);

        return $response;
    }
}
