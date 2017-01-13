<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paynow\Paynow\Block\Payment;

/**
 * PayNow common payment info block
 * Uses default templates
 */
class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var \Paynow\Paynow\Model\InfoFactory
     */
    protected $_paynowInfoFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Paynow\Paynow\Model\InfoFactory $paynowInfoFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Paynow\Paynow\Model\InfoFactory $paynowInfoFactory,
        array $data = []
    ) {
        $this->_paynowInfoFactory = $paynowInfoFactory;
        parent::__construct($context, $data);
    }

}
