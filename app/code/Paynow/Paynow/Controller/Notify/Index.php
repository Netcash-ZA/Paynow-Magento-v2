<?php

namespace Paynow\Paynow\Controller\Notify;

class Index extends \Paynow\Paynow\Controller\AbstractPaynow
{
    private $storeId;

	/**
	 * Check if this is a 'callback' stating the transaction is pending.
	 */
	private function pn_is_pending() {
		return isset($_POST['TransactionAccepted'])
		       && $_POST['TransactionAccepted'] == 'false'
		       && stristr($_POST['Reason'], 'pending');
	}

	private function _pn_do_transaction() {

		// Variable Initialization
		$pnError = false;
		$pnErrMsg = '';
		$pnData = array();
		$serverMode = $this->getConfigData('server');
		$pnParamString = '';

		$pnHost = $this->_paymentMethod->getPaynowHost( $serverMode );

		pnlog( ' PayNow ITN call received' );

		pnlog( 'Server = ' . $pnHost );

		//// Notify PayNow that information has been received
		if( !$pnError )
		{
//			header( 'HTTP/1.0 200 OK' );
//			flush();
		}

		//// Get data sent by PayNow
		if( !$pnError )
		{
			// Posted variables from ITN
			$pnData = pnGetData();

			if ( empty( $pnData ) )
			{
				$pnError = true;
				$pnErrMsg = PN_ERR_BAD_ACCESS;
			}
		}

		if( isset($_POST) && !empty($_POST) && !$this->pn_is_pending() ) {

			if (!isset($pnData['TransactionAccepted']) || $pnData['TransactionAccepted'] == 'false') {
				$pnError = true;
				$pnErrMsg = PN_MSG_FAILED;
			}

		} else {


			// Probably calling the "redirect" URL
			pnlog('Probably calling redirect url');
			// $this->_redirect($url_for_redirect);
			return $this->_redirect("customer/account");

		}


		//// Verify source IP (If not in debug mode)
//		if( !$pnError && !defined( 'PN_DEBUG' ) )
//		{
//			pnlog( 'Verify source IP' );
//
//			if( !pnValidIP( $_SERVER['REMOTE_ADDR'] , $serverMode ) )
//			{
//				$pnError = true;
//				$pnErrMsg = PN_ERR_BAD_SOURCE_IP;
//			}
//		}

		//// Get internal order and verify it hasn't already been processed
		if( !$pnError )
		{
			pnlog( "Check order hasn't been processed" );

			// Load order
			$orderId = $pnData['Reference'];

			$this->_order = $this->_orderFactory->create()->loadByIncrementId($orderId);

			$this->storeId = $this->_order->getStoreId();


			pnlog( 'order status is : ' . $this->_order->getStatus());

			// Check order is in "pending payment" state
			if( $this->_order->getStatus() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT )
			{
				$pnError = true;
				$pnErrMsg = PN_ERR_ORDER_PROCESSED;

				// Redirect to order page
				return $this->_redirect('sales/order/view', array( 'order_id'=> $orderId ) );
//				header("Location: $order_url");
			}
		}

		//// Verify data received
		if( !$pnError )
		{
			pnlog( 'Verify data received' );

			$pfValid = pnValidData( $pnHost, $pnParamString );

			if( !$pfValid )
			{
				$pnError = true;
				$pnErrMsg = PN_ERR_BAD_ACCESS;
			}
		}

		//// Check status and update order
		if( !$pnError )
		{
			pnlog( 'Check status and update order' );

			// Successful
			if( $pnData['TransactionAccepted'] == "true" )
			{
				pnlog( 'Order complete' );

				// Currently order gets set to "Pending" even if invoice is paid.
				// Looking at http://stackoverflow.com/a/18711371 (http://stackoverflow.com/questions/18711176/how-to-set-order-status-as-complete-in-magento)
				//  it is suggested that this is normal behaviour and an order is only "complete" after shipment
				// 2 Options.
				//  a. Leave as is. (Recommended)
				//  b. Force order complete status (http://stackoverflow.com/a/18711313)

				// Update order additional payment information
				$payment = $this->_order->getPayment();
				$payment->setAdditionalInformation( "TransactionAccepted", $pnData['TransactionAccepted'] );
				$payment->setAdditionalInformation( "Reference", $pnData['Reference'] );
				$payment->setAdditionalInformation( "PNTrace", $pnData['RequestTrace'] );
//				$payment->setAdditionalInformation( "email_address", $pnData['email_address'] );
				$payment->setAdditionalInformation( "Amount", $pnData['Amount'] );
//				$payment->registerCaptureNotification( $pnData['amount_gross'], true);
				$payment->save();

				// Save invoice
				$this->saveInvoice();

			}
		}

		// If an error occurred
		if( $pnError )
		{
			pnlog( 'Error occurred: ' . $pnErrMsg );
			$this->_logger->critical( "Error occured : ". $pnErrMsg );
		}
	}

    /**
     * indexAction
     *
     * Instantiate ITN model and pass ITN request to it
     */
    public function execute()
    {

        $pre = __METHOD__ . " : ";
        $this->_logger->debug( $pre . 'bof' );

	    if( isset($_POST) && !empty($_POST) ) {

		    // This is the notification coming in!
		    // Act as an IPN request and forward request to Credit Card method.
		    // Logic is exactly the same

		    return $this->_pn_do_transaction();
		    die();

	    }

	    die( PN_ERR_BAD_ACCESS );
    }

    /**
	 * saveInvoice
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	protected function saveInvoice()
    {
        pnlog( 'Saving invoice' );

		// Check for mail msg
		$invoice = $this->_order->prepareInvoice();

		$invoice->register()->capture();

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

	$invoiceSender = $this->_objectManager->get('Magento\Sales\Model\Order\Email\Sender\InvoiceSender');
        $invoiceSender->send($invoice);
	$history->setIsCustomerNotified(true);
	$history->save();

        $this->_order->addStatusHistoryComment( __( 'Notified customer about invoice #%1.', $invoice->getIncrementId() ) );
        $this->_order->save();
    }
}
