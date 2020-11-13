<?php

namespace SwedbankPay\Core;

use SwedbankPay\Core\Api\Authorization;
use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Api\Transaction;
use SwedbankPay\Core\Api\Verification;

interface CoreInterface
{
    const INTENT_AUTOCAPTURE = 'AutoCapture';
    const INTENT_AUTHORIZATION = 'Authorization';
    const INTENT_SALE = 'Sale';

    const OPERATION_PURCHASE = 'Purchase';
    const OPERATION_VERIFY = 'Verify';
    const OPERATION_RECUR = 'Recur';
    const OPERATION_UNSCHEDULED_PURCHASE = 'UnscheduledPurchase';
    const OPERATION_FINANCING_CONSUMER = 'FinancingConsumer';
    const TYPE_CREDITCARD = 'CreditCard';

    /**
     * Initiate a Credit Card Payment
     *
     * @param mixed $orderId
     * @param bool $generateToken
     * @param string $paymentToken
     *
     * @return Response
     * @throws Exception
     */
    public function initiateCreditCardPayment($orderId, $generateToken, $paymentToken);

    /**
     * Initiate Verify Card Payment
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function initiateVerifyCreditCardPayment($orderId);

    /**
     * Initiate a CreditCard Recurrent Payment
     *
     * @param mixed $orderId
     * @param string $recurrenceToken
     * @param string|null $paymentToken
     *
     * @return Response
     * @throws \Exception
     */
    public function initiateCreditCardRecur($orderId, $recurrenceToken, $paymentToken = null);

    /**
     * Initiate a CreditCard Unscheduled Purchase
     *
     * @param mixed $orderId
     * @param string $recurrenceToken
     * @param string|null $paymentToken
     *
     * @return Response
     * @throws \Exception
     */
    public function initiateCreditCardUnscheduledPurchase($orderId, $recurrenceToken, $paymentToken = null);

    /**
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function initiateInvoicePayment($orderId);

    /**
     * Get Approved Legal Address.
     *
     * @param string $legalAddressHref
     * @param string $socialSecurityNumber
     * @param string $postCode
     *
     * @return Response
     * @throws Exception
     */
    public function getApprovedLegalAddress($legalAddressHref, $socialSecurityNumber, $postCode);

    /**
     * Initiate a Financing Consumer Transaction
     *
     * @param string $authorizeHref
     * @param string $orderId
     * @param string $ssn
     * @param string $addressee
     * @param string $coAddress
     * @param string $streetAddress
     * @param string $zipCode
     * @param string $city
     * @param string $countryCode
     *
     * @return Response
     * @throws Exception
     */
    public function transactionFinancingConsumer(
        $authorizeHref,
        $orderId,
        $ssn,
        $addressee,
        $coAddress,
        $streetAddress,
        $zipCode,
        $city,
        $countryCode
    );

    /**
     * Capture Invoice.
     *
     * @param mixed $orderId
     * @param int|float $amount
     * @param int|float $vatAmount
     * @param array $items
     *
     * @return Response
     * @throws Exception
     */
    public function captureInvoice($orderId, $amount = null, $vatAmount = 0, array $items = []);

    /**
     * Cancel Invoice.
     *
     * @param mixed $orderId
     * @param int|float|null $amount
     * @param int|float $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function cancelInvoice($orderId, $amount = null, $vatAmount = 0);

    /**
     * Refund Invoice.
     *
     * @param mixed $orderId
     * @param int|float|null $amount
     * @param int|float $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function refundInvoice($orderId, $amount = null, $vatAmount = 0);

    /**
     * Can Capture.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     */
    public function canCapture($orderId, $amount = null);

    /**
     * Can Cancel.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     */
    public function canCancel($orderId, $amount = null);

    /**
     * Can Refund.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     */
    public function canRefund($orderId, $amount = null);

    /**
     * Capture.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function capture($orderId, $amount = null);

    /**
     * Cancel.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function cancel($orderId, $amount = null);

    /**
     * Refund.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function refund($orderId, $amount = null, $reason = null);

    /**
     * Abort Payment.
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function abort($orderId);

    /**
     * Check if order status can be updated.
     *
     * @param mixed $orderId
     * @param string $status
     * @param string|null $transactionId
     * @return bool
     */
    public function canUpdateOrderStatus($orderId, $status, $transactionId = null);

	/**
	 * Get Order Status.
	 *
	 * @param mixed $orderId
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getOrderStatus($orderId);

	/**
	 * Set Payment Id to Order.
	 *
	 * @param mixed $orderId
	 * @param string $paymentId
	 *
	 * @return void
	 */
	public function setPaymentId($orderId, $paymentId);

	/**
	 * Set Payment Order Id to Order.
	 *
	 * @param mixed $orderId
	 * @param string $paymentOrderId
	 *
	 * @return void
	 */
	public function setPaymentOrderId($orderId, $paymentOrderId);

    /**
     * Update Order Status.
     *
     * @param mixed $orderId
     * @param string $status
     * @param string|null $message
     * @param string|null $transactionId
     */
    public function updateOrderStatus($orderId, $status, $message = null, $transactionId = null);

    /**
     * Add Order Note.
     *
     * @param mixed $orderId
     * @param string $message
     */
    public function addOrderNote($orderId, $message);

	/**
	 * Get Payment Method.
	 *
	 * @param mixed $orderId
	 *
	 * @return string|null Returns method or null if not exists
	 */
	public function getPaymentMethod($orderId);

    /**
     * Fetch Transactions related to specific order, process transactions and
     * update order status.
     *
     * @param mixed $orderId
     * @param string|null $transactionId
     * @throws Exception
     */
    public function fetchTransactionsAndUpdateOrder($orderId, $transactionId = null);

    /**
     * @param $orderId
     * @param Api\Transaction|array $transaction
     *
     * @throws Exception
     */
    public function processTransaction($orderId, $transaction);

    /**
     * @param $orderId
     *
     * @return string
     */
    public function generatePayeeReference($orderId);

    /**
     * Do API Request
     *
     * @param       $method
     * @param       $url
     * @param array $params
     *
     * @return Response
     * @throws \Exception
     */
    public function request($method, $url, $params = []);

    /**
     * Fetch Payment Info.
     *
     * @param string $paymentIdUrl
     * @param string|null $expand
     *
     * @return Response
     * @throws Exception
     */
    public function fetchPaymentInfo($paymentIdUrl, $expand = null);

    /**
     * Fetch Transaction List.
     *
     * @param string $paymentIdUrl
     * @param string|null $expand
     *
     * @return Transaction[]
     * @throws Exception
     */
    public function fetchTransactionsList($paymentIdUrl, $expand = null);

    /**
     * Fetch Verification List.
     *
     * @param string $paymentIdUrl
     * @param string|null $expand
     *
     * @return Verification[]
     * @throws Exception
     */
    public function fetchVerificationList($paymentIdUrl, $expand = null);

    /**
     * Fetch Authorization List.
     *
     * @param string $paymentIdUrl
     * @param string|null $expand
     *
     * @return Authorization[]
     * @throws Exception
     */
    public function fetchAuthorizationList($paymentIdUrl, $expand = null);

    /**
     * Initiate Swish Payment
     *
     * @param mixed $orderId
     * @param string $phone
     * @param bool $ecomOnlyEnabled
     *
     * @return Response
     * @throws Exception
     */
    public function initiateSwishPayment($orderId, $phone, $ecomOnlyEnabled = true);

    /**
     * initiate Swish Payment Direct
     *
     * @param string $saleHref
     * @param string $phone
     *
     * @return mixed
     * @throws Exception
     */
    public function initiateSwishPaymentDirect($saleHref, $phone);

    /**
     * Save Transaction Data.
     *
     * @param mixed $orderId
     * @param array $transactionData
     */
    public function saveTransaction($orderId, $transactionData = []);

    /**
     * Save Transactions Data.
     *
     * @param mixed $orderId
     * @param array $transactions
     */
    public function saveTransactions($orderId, array $transactions);

    /**
     * Find Transaction.
     *
     * @param string $field
     * @param mixed $value
     *
     * @return bool|Transaction
     */
    public function findTransaction($field, $value);

    /**
     * Initiate Vipps Payment.
     *
     * @param mixed $orderId
     * @param string $phone
     *
     * @return mixed
     * @throws Exception
     */
    public function initiateVippsPayment($orderId, $phone);

    /**
     * Initiate Payment Order Purchase.
     *
     * @param mixed $orderId
     * @param string|null $consumerProfileRef
     * @param bool $generateRecurrenceToken
     *
     * @return Response
     * @throws Exception
     */
    public function initiatePaymentOrderPurchase(
        $orderId,
        $consumerProfileRef = null,
        $generateRecurrenceToken = false
    );

    /**
     * Initiate Payment Order Verify
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function initiatePaymentOrderVerify($orderId);

    /**
     * Initiate Payment Order Recurrent Payment
     *
     * @param mixed $orderId
     * @param string $recurrenceToken
     *
     * @return Response
     * @throws \Exception
     */
    public function initiatePaymentOrderRecur($orderId, $recurrenceToken);

    /**
     * @param string $updateUrl
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function updatePaymentOrder($updateUrl, $orderId);

    /**
     * Get Payment ID url by Payment Order.
     *
     * @param string $paymentOrderId
     *
     * @return string|false
     */
    public function getPaymentIdByPaymentOrder($paymentOrderId);

    /**
     * Get Current Payment Resource.
     * The currentpayment resource displays the payment that are active within the payment order container.
     *
     * @param string $paymentOrderId
     * @return array|false
     */
    public function getCheckoutCurrentPayment($paymentOrderId);

    /**
     * Capture Checkout.
     *
     * @param mixed $orderId
     * @param int|float $amount
     * @param int|float $vatAmount
     * @param array $items
     *
     * @return Response
     * @throws Exception
     */
    public function captureCheckout($orderId, $amount = null, $vatAmount = 0, array $items = []);

    /**
     * Cancel Checkout.
     *
     * @param mixed $orderId
     * @param int|float|null $amount
     * @param int|float $vatAmount
     *
     * @return Response
     * @throws Exception
     */
    public function cancelCheckout($orderId, $amount = null, $vatAmount = 0);

    /**
     * Refund Checkout.
     *
     * @param mixed $orderId
     * @param int|float|null $amount
     * @param int|float $vatAmount
     * @param array $items
     *
     * @return Response
     * @throws Exception
     */
    public function refundCheckout($orderId, $amount = null, $vatAmount = 0, array $items = []);

    /**
     * Log a message.
     *
     * @param string $level See LogLevel
     * @param string $message Message
     * @param array $context Context
     */
    public function log($level, $message, array $context = []);
}