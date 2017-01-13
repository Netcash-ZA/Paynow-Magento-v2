<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paynow\Paynow\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Paynow\Paynow\Helper\Data as PaynowHelper;

class PaynowConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /** @var PaynowHelper */
    protected $paynowHelper;

    /**
     * @var string[]
     */
    protected $methodCodes = [
        Config::METHOD_CODE
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @param ConfigFactory $configFactory
     * @param ResolverInterface $localeResolver
     * @param CurrentCustomer $currentCustomer
     * @param PaynowHelper $paymentHelper
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConfigFactory $configFactory,
        ResolverInterface $localeResolver,
        CurrentCustomer $currentCustomer,
        PaynowHelper $paynowHelper,
        PaymentHelper $paymentHelper
    ) {
        $this->_logger = $logger;
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $this->localeResolver = $localeResolver;
        $this->config = $configFactory->create();
        $this->currentCustomer = $currentCustomer;
        $this->paynowHelper = $paynowHelper;
        $this->paymentHelper = $paymentHelper;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->paymentHelper->getMethodInstance($code);
        }

        $this->_logger->debug( $pre . 'eof and this  methods has : ', $this->methods );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');
        $config = [
            'payment' => [
                'paynow' => [
                    'paymentAcceptanceMarkSrc' => $this->config->getPaymentMarkImageUrl(),
                    'paymentAcceptanceMarkHref' => $this->config->getPaymentMarkWhatIsPaynow(),
                ]
            ]
        ];

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['paynow']['redirectUrl'][$code] = $this->getMethodRedirectUrl($code);
                $config['payment']['paynow']['billingAgreementCode'][$code] = $this->getBillingAgreementCode($code);

            }
        }
        $this->_logger->debug($pre . 'eof', $config);
        return $config;
    }

    /**
     * Return redirect URL for method
     *
     * @param string $code
     * @return mixed
     */
    protected function getMethodRedirectUrl($code)
    {
        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $methodUrl = $this->methods[$code]->getCheckoutRedirectUrl();

        $this->_logger->debug($pre . 'eof');
        return $methodUrl;
    }

    /**
     * Return billing agreement code for method
     *
     * @param string $code
     * @return null|string
     */
    protected function getBillingAgreementCode($code)
    {

        $pre = __METHOD__ . ' : ';
        $this->_logger->debug($pre . 'bof');

        $customerId = $this->currentCustomer->getCustomerId();
        $this->config->setMethod($code);

        $this->_logger->debug($pre . 'eof');

        // always return null
        return $this->paynowHelper->shouldAskToCreateBillingAgreement($this->config, $customerId);
    }
}
