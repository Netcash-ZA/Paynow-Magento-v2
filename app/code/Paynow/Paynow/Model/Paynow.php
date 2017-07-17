<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paynow\Paynow\Model;

include_once( dirname( __FILE__ ) .'/../Model/paynow_common.inc' );

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Paynow extends \Magento\Payment\Model\Method\AbstractMethod
{
	/**
	 * @var string
	 */
	protected $_code = Config::METHOD_CODE;

	/**
	 * @var string
	 */
	protected $_formBlockType = 'Paynow\Paynow\Block\Form';

	/**
	 * @var string
	 */
	protected $_infoBlockType = 'Paynow\Paynow\Block\Payment\Info';

	/** @var string */
	protected $_configType = 'Paynow\Paynow\Model\Config';

	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_isInitializeNeeded = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_isGateway = false;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canOrder = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canAuthorize = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canCapture = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canVoid = false;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canUseInternal = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canUseCheckout = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canFetchTransactionInfo = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canReviewPayment = true;

	/**
	 * Website Payments Pro instance
	 *
	 * @var \Paynow\Paynow\Model\Config $config
	 */
	protected $_config;
	/**
	 * Payment additional information key for payment action
	 *
	  * @var string
	 */
	protected $_isOrderPaymentActionKey = 'is_order_action';

	/**
	 * Payment additional information key for number of used authorizations
	 *
	 * @var string
	 */
	protected $_authorizationCountKey = 'authorization_count';

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	protected $_storeManager;

	/**
	 * @var \Magento\Framework\UrlInterface
	 */
	protected $_urlBuilder;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $_checkoutSession;

	/**
	 * @var \Magento\Framework\Exception\LocalizedExceptionFactory
	 */
	protected $_exception;

	/**
	 * @var \Magento\Sales\Api\TransactionRepositoryInterface
	 */
	protected $transactionRepository;

	/**
	 * @var Transaction\BuilderInterface
	 */
	protected $transactionBuilder;

	/**
	 * @param \Magento\Framework\Model\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
	 * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
	 * @param \Magento\Payment\Helper\Data $paymentData
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Payment\Model\Method\Logger $logger
	 * @param \Paynow\Paynow\Model\ConfigFactory $configFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Framework\UrlInterface $urlBuilder
	 * @param \Paynow\Paynow\Model\CartFactory $cartFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Framework\Exception\LocalizedExceptionFactory $exception
	 * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
	 * @param Transaction\BuilderInterface $transactionBuilder
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
	 * @param array $data
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct( \Magento\Framework\Model\Context $context,
								 \Magento\Framework\Registry $registry,
								 \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
								 \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
								 \Magento\Payment\Helper\Data $paymentData,
								 \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
								 \Magento\Payment\Model\Method\Logger $logger,
								 ConfigFactory $configFactory,
								 \Magento\Store\Model\StoreManagerInterface $storeManager,
								 \Magento\Framework\UrlInterface $urlBuilder,
								 \Magento\Checkout\Model\Session $checkoutSession,
								 \Magento\Framework\Exception\LocalizedExceptionFactory $exception,
								 \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
								 \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
								 \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
								 \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
								 array $data = [ ] )
	{
		parent::__construct( $context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $resource, $resourceCollection, $data );
		$this->_storeManager = $storeManager;
		$this->_urlBuilder = $urlBuilder;
		$this->_checkoutSession = $checkoutSession;
		$this->_exception = $exception;
		$this->transactionRepository = $transactionRepository;
		$this->transactionBuilder = $transactionBuilder;

		$parameters = [ 'params' => [ $this->_code ] ];

		$this->_config = $configFactory->create( $parameters );

		if (! defined('PN_DEBUG'))
		{
			define('PN_DEBUG', $this->getConfigData('debug'));
		}

	}


	/**
	 * Store setter
	 * Also updates store ID in config object
	 *
	 * @param \Magento\Store\Model\Store|int $store
	 *
	 * @return $this
	 */
	public function setStore( $store )
	{
		$this->setData( 'store', $store );

		if ( null === $store )
		{
			$store = $this->_storeManager->getStore()->getId();
		}
		$this->_config->setStoreId( is_object( $store ) ? $store->getId() : $store );

		return $this;
	}


	/**
	 * Whether method is available for specified currency
	 *
	 * @param string $currencyCode
	 *
	 * @return bool
	 */
	public function canUseForCurrency( $currencyCode )
	{
		return $this->_config->isCurrencyCodeSupported( $currencyCode );
	}

	/**
	 * Payment action getter compatible with payment model
	 *
	 * @see \Magento\Sales\Model\Payment::place()
	 * @return string
	 */
	public function getConfigPaymentAction()
	{
		return $this->_config->getPaymentAction();
	}

	/**
	 * Check whether payment method can be used
	 *
	 * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
	 *
	 * @return bool
	 */
	public function isAvailable( \Magento\Quote\Api\Data\CartInterface $quote = null )
	{
		return parent::isAvailable( $quote ) && $this->_config->isMethodAvailable();
	}


	/**
	 * @return mixed
	 */
	protected function getStoreName()
	{
		$pre = __METHOD__ . " : ";
		pnlog( $pre . 'bof' );

		$storeName = $this->_scopeConfig->getValue(
			'general/store_information/name',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE
		);

		pnlog( $pre . 'store name is ' . $storeName );

		return $storeName;
	}

	/**
	 * Place an order with authorization or capture action
	 *
	 * @param Payment $payment
	 * @param float $amount
	 *
	 * @return $this
	 */
	protected function _placeOrder( Payment $payment, $amount )
	{

		$pre = __METHOD__ . " : ";
		$this->_logger->debug( $pre . 'bof' );

	}

	/**
	 * this where we compile data posted by the form to paynow
	 * @return array
	 */
	public function getStandardCheckoutFormFields()
	{
		$pre = __METHOD__ . ' : ';
		// Variable initialization

		$order = $this->_checkoutSession->getLastRealOrder();

		$description = '';

		$this->_logger->debug($pre . 'serverMode : '. $this->getConfigData( 'server' ));

		// If NOT test mode, use normal credentials
//        if( $this->getConfigData( 'server' ) == 'live' ) {
			$merchantId = $this->getConfigData( 'merchant_id' );
		$serviceKey = $this->getConfigData( 'service_key' );
//        }
		$sageGUID = "f0f593a7-338d-406b-b340-5b4acd50f627";

		// Create description
		foreach( $order->getAllItems() as $items ) {
			$description .= intval( $items->getQtyOrdered() ) . ' x ' . $items->getName() .';';
		}

		$customerID = $order->getData('customer_id');
		$customerName = $order->getData('customer_firstname') . " " . $order->getData('customer_lastname');
		$orderID = $order->getRealOrderId();


		$pnDescription = trim(substr( "$description - ({$customerName})", 0, 254 ));

		// Construct data for the form
		$data = array(
			// Merchant details

			'm1' => $serviceKey,
			'm2' => $sageGUID,
			'return_url' => $this->getPaidSuccessUrl(),
			'cancel_url' => $this->getPaidCancelUrl(),
			'notify_url' => $this->getPaidNotifyUrl(),

			// Buyer details
			'm9' => $order->getData( 'customer_email' ),

			'p3' => $pnDescription,
			'm4' => "{$customerID}",

			// Item details
			'p4' => $this->getTotalAmount( $order ),
			'p2' => $orderID

		);

		$pnOutput = '';
		// Create output string
		foreach( $data as $key => $val )
		{
			if (!empty( $val ))
			{
				$pnOutput .= $key .'='. urlencode( $val ) .'&';
			}
		}

		$passPhrase = $this->getConfigData('passphrase');
		$pnOutput = substr( $pnOutput, 0, -1 );

		if ( !empty( $passPhrase ) && $this->getConfigData('server') !== 'test' )
		{
			$pnOutput = $pnOutput."&passphrase=".urlencode( $passPhrase );
		}

		pnlog( $pre . 'pnOutput for signature is : ' . $pnOutput );

		$pfSignature = md5( $pnOutput );

		$data['signature'] = $pfSignature;
		$data['user_agent'] = 'Magento 2.0';
		pnlog( $pre . 'data is :' . print_r( $data, true ) );
		return( $data );
	}


	/**
	 * getTotalAmount
	 */
	public function getTotalAmount( $order )
	{
		if( $this->getConfigData( 'use_store_currency' ) )
			$price = $this->getNumberFormat( $order->getGrandTotal() );
		else
			$price = $this->getNumberFormat( $order->getBaseGrandTotal() );

		return $price;
	}

	/**
	 * getNumberFormat
	 */
	public function getNumberFormat( $number )
	{
		return number_format( $number, 2, '.', '' );
	}

	/**
	 * getPaidSuccessUrl
	 */
	public function getPaidSuccessUrl()
	{
		return $this->_urlBuilder->getUrl( 'paynow/redirect/success', array( '_secure' => true ) );
	}

	/**
	 * Get transaction with type order
	 *
	 * @param OrderPaymentInterface $payment
	 *
	 * @return false|\Magento\Sales\Api\Data\TransactionInterface
	 */
	protected function getOrderTransaction( $payment )
	{
		return $this->transactionRepository->getByTransactionType( Transaction::TYPE_ORDER, $payment->getId(), $payment->getOrder()->getId() );
	}

	/*
	 * called dynamically by checkout's framework.
	 *
	 * Not used anymore  according to https://github.com/magento/magento2/issues/2241#issuecomment-155471428
	 */
	public function getOrderPlaceRedirectUrl()
	{
		$pre = __METHOD__ . " : ";

		$url = $this->_urlBuilder->getUrl( 'paynow/redirect' );

		pnlog( "{$pre} -> {$url} : " . 'bof' );
		return $url;

	}
	 /**
	 * Checkout redirect URL getter for onepage checkout (hardcode)
	 *
	 * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
	 * @see Quote\Payment::getCheckoutRedirectUrl()
	 * @return string
	 */
	public function getCheckoutRedirectUrl()
	{
		$pre = __METHOD__ . " : ";

		$url = $this->_urlBuilder->getUrl( 'paynow/redirect' );

		pnlog( "{$pre} -> {$url} : " . 'bof' );

		return $url;
	}

	/**
	 *
	 * @param string $paymentAction
	 * @param object $stateObject
	 *
	 * @return $this
	 */
	public function initialize( $paymentAction, $stateObject )
	{
		$pre = __METHOD__ . " : ";
		pnlog( $pre . 'bof' );

		$stateObject->setState( \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT );
		$stateObject->setStatus('pending_payment');
		$stateObject->setIsNotified( false );

		return parent::initialize( $paymentAction, $stateObject ); // TODO: Change the autogenerated stub

	}

	/**
	 * getPaidCancelUrl
	 */
	public function getPaidCancelUrl()
	{
		return $this->_urlBuilder->getUrl( 'paynow/redirect/cancel', array( '_secure' => true ) );
	}
	/**
	 * getPaidNotifyUrl
	 */
	public function getPaidNotifyUrl()
	{
		return $this->_urlBuilder->getUrl( 'paynow/notify', array( '_secure' => true ) );
	}

	/**
	 * getPayNowUrl
	 *
	 * Get URL for form submission to PayNow.
	 */
	public function getPaynowUrl()
	{
		return( 'https://'. $this->getPaynowHost( $this->getConfigData('server') ) . '/site/paynow.aspx' );
	}

	/**
	 * @param $serverMode
	 *
	 * @return string
	 */
	public function getPaynowHost( $serverMode )
	{
		$url = 'paynow.sagepay.co.za';
		return $url;
	}
}
