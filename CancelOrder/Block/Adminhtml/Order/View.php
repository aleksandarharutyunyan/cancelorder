<?php

namespace Aiops\CancelOrder\Block\Adminhtml\Order;

class View extends \Magento\Sales\Block\Adminhtml\Order\View
{

    /**
     * config path for all statuses that can be moved to canceled
     */
    const CANCEL_ORDER_ADDITIONAL_STATUSES = "cancel_order/general/order_status_cancel_allowed";

    /**
     * Block group
     *
     * @var string
     */
    protected $_blockGroup = 'Magento_Sales';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Sales config
     *
     * @var \Magento\Sales\Model\Config
     */
    protected $_salesConfig;

    /**
     * Reorder helper
     *
     * @var \Magento\Sales\Helper\Reorder
     */
    protected $_reorderHelper;

    /**
     * scopeConfigInterface
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Magento\Sales\Helper\Reorder $reorderHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Sales\Helper\Reorder $reorderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        array $data = []
    ) {
        $this->_reorderHelper = $reorderHelper;
        $this->_coreRegistry = $registry;
        $this->_salesConfig = $salesConfig;
        $this->scopeConfigInterface = $scopeConfigInterface;
        parent::__construct($context, $registry, $salesConfig, $reorderHelper, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $order = $this->getOrder();
        $this->addMoveToProcessingButton($order);
        if (!$this->_isAllowedAction('Magento_Sales::cancel') || !$order->canCancel()) {
            $this->addModifiedCancelButton($order);
        }
    }

    /**
     * @param $order
     */
    private function addModifiedCancelButton($order)
    {
        $cancel_statuses = $this->getAdditionalCancelStatuses(self::CANCEL_ORDER_ADDITIONAL_STATUSES);
        if (count($cancel_statuses) && in_array($order->getStatus(), $cancel_statuses)) {
            $this->addButton(
                'order_cancel',
                [
                    'label' => __('Cancel'),
                    'class' => 'cancel',
                    'id' => 'order-view-cancel-button',
                    'data_attribute' => [
                        'url' => $this->getCancelUrl()
                    ]
                ]
            );
        }
    }

    /**
     * @param $order
     */
    private function addMoveToProcessingButton($order)
    {
        if ($order->getStatus() == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
            $message = __(
                'Are you sure you want to move this order to processing status?'
            );
            $onClick = "confirmSetLocation('{$message}', '{$this->getMoveToProcessingUrl()}')";

            $this->addButton(
                'order_movetoprocessing',
                [
                    'label' => __('Move to Processing'),
                    'class' => 'movetoprocessing',
                    'id' => 'order-view-move-to-processing-button',
                    'onclick' => $onClick,
                    'data_attribute' => [
                        'url' => $this->getMoveToProcessingUrl()
                    ]
                ]
            );
        }
    }

    /**
     * @param $value
     * @return array
     */
    private function getAdditionalCancelStatuses($value)
    {
        $cancel_statuses = $this->getConfigValue($value);
        return explode(',', $cancel_statuses);
    }

    /**
     * @param $value
     * @return mixed
     */
    private function getConfigValue($value)
    {
        return $this->scopeConfigInterface->getValue($value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Move to processing URL getter
     *
     * @return string
     */
    public function getMoveToProcessingUrl()
    {
        return $this->getUrl('aiops/order/movetoprocessing');
    }
}
