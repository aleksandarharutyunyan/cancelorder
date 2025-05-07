<?php

namespace Aiops\CancelOrder\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class Movetoprocessing extends \Magento\Sales\Controller\Adminhtml\Order
{

    /**
     * const for pending status
     */
    const SYNOLIA_PENDING_STATUS = 'pending';
    /**
     * const for flow name
     */
    const SYNOLIA_FLOW_NAME = 'm3_export_orders';

    /**
     * const for prepared to process table name
     */
    const SYNOLIA_SYNC_PROCESSED_TABLE = 'synolia_sync_processed';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Movetoprocessing constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param OrderManagementInterface $orderManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    /**
     * Execute action
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $order = $this->_initOrder();
        if ($order) {
            try {
                if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {

                    $payment = $order->getPayment();

                    $amount = $order->getGrandTotal();
                    $payment->registerAuthorizationNotification($amount);
                    $payment->registerCaptureNotification($amount);

                    $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)->setState(
                        \Magento\Sales\Model\Order::STATE_PROCESSING
                    )->save();

                    $this->insertRecordForM3Export($order);

                    $this->messageManager->addSuccessMessage(__('Status moved to processing.'));
                } else {
                    $this->messageManager->addErrorMessage('Status cannot be moved to processing.');
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Status cannot be moved to processing.' . $e->getMessage()));
            }
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }

    /**
     * @param $order
     * @throws \Zend_Date_Exception
     */
    protected function insertRecordForM3Export($order)
    {
        $resource = $this->resourceConnection;
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName(self::SYNOLIA_SYNC_PROCESSED_TABLE);
        $now = new \Zend_Date();
        $syncProcessRecord = [
            'code' => self::SYNOLIA_PENDING_STATUS,
            'flow_name' => self::SYNOLIA_FLOW_NAME,
            'created_at' => $now->toString("yyyy-MM-dd HH:mm:ss"),
            'id' => $order->getId()
        ];
        $connection->insert($tableName, $syncProcessRecord);
    }
}
