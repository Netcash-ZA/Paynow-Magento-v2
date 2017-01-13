<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paynow\Paynow\Block\Paynow;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Paynow\Paynow\Model\Config;
use Paynow\Paynow\Model\Paynow\Checkout;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string Payment method code */
    protected $_methodCode = Config::METHOD_CODE;

    /** @var \Paynow\Paynow\Helper\Data */
    protected $_paynowData;

    /** @var \Paynow\Paynow\Model\ConfigFactory */
    protected $paynowConfigFactory;

    /** @var ResolverInterface */
    protected $_localeResolver;

    /** @var \Paynow\Paynow\Model\Config */
    protected $_config;

    /** @var bool */
    protected $_isScopePrivate;

    /** @var CurrentCustomer */
    protected $currentCustomer;

    /**
     * @param Context $context
     * @param \Paynow\Paynow\Model\ConfigFactory $paynowConfigFactory
     * @param ResolverInterface $localeResolver
     * @param \Paynow\Paynow\Helper\Data $paynowData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Paynow\Paynow\Model\ConfigFactory $paynowConfigFactory,
        ResolverInterface $localeResolver,
        \Paynow\Paynow\Helper\Data $paynowData,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_paynowData = $paynowData;
        $this->paynowConfigFactory = $paynowConfigFactory;
        $this->_localeResolver = $localeResolver;
        $this->_config = null;
        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
        $this->_logger->debug( $pre . "eof" );
    }

    /**
     * Set template and redirect message
     *
     * @return null
     */
    protected function _construct()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );
        $this->_config = $this->paynowConfigFactory->create()->setMethod( $this->getMethodCode() );
        parent::_construct();
    }

    /**
     * Payment method code getter
     *
     * @return string
     */
    public function getMethodCode()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

        return $this->_methodCode;
    }




}
