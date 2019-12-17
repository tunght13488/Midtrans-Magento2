<?php

namespace Midtrans\Snap\Controller\Payment;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

$object_manager = \Magento\Framework\App\ObjectManager::getInstance();
$filesystem = $object_manager->get('Magento\Framework\Filesystem');
$root = $filesystem->getDirectoryRead(DirectoryList::ROOT);
$lib_file = $root->getAbsolutePath('lib/internal/midtrans-php/Midtrans.php');
require_once($lib_file);

class Cancel extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_checkoutSession;
    protected $_logger;
    protected $_coreSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Session\SessionManagerInterface $coreSession
    )
    {
        parent::__construct($context);
        $this->_coreSession = $coreSession;
    }

    public function execute()
    {
        $om = $this->_objectManager;
        $orderId = $this->getValue();

        $order = $om->get('Magento\Sales\Model\Order')->loadByIncrementId($orderId);
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_NEW) {

            $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->save();

            $order->getPayment()->cancel();
            $order->registerCancellation();

            $this->unSetValue();
            return $this->resultRedirectFactory->create()->setPath('snap/index/close');
        } else {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
    }

    public function getValue()
    {
        $this->_coreSession->start();
        return $this->_coreSession->getMessage();
    }

    public function unSetValue()
    {
        $this->_coreSession->start();
        return $this->_coreSession->unsMessage();
    }

}
