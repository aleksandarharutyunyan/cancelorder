<?php


namespace Aiops\CancelOrder\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Class Cancel
 * Aiops\CancelOrder\Controller\Adminhtml\Order
 */
class Cancel extends \Magento\Sales\Controller\Adminhtml\Order\Cancel
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::cancel';

    /**
     * Cancel order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        $order = $this->_initOrder();
        if ($order) {
            try {
                if (!$order->canCancel()) {
                    $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED)->setState(
                        \Magento\Sales\Model\Order::STATE_CANCELED
                    )->save();
                }
                $this->orderManagement->cancel($order->getEntityId());
                $this->messageManager->addSuccessMessage(__('You canceled the order.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            }
            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }
}
