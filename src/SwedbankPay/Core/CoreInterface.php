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
    const OPERATION_UPDATE_ORDER = 'UpdateOrder';

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
     * @param string|null $transactionNumber
     * @return bool
     */
    public function canUpdateOrderStatus($orderId, $status, $transactionNumber = null);

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
     * @param string|null $transactionNumber
     */
    public function updateOrderStatus($orderId, $status, $message = null, $transactionNumber = null);

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
     * @param string|null $transactionNumber
     * @throws Exception
     */
    public function fetchTransactionsAndUpdateOrder($orderId, $transactionNumber = null);

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
     * Create Credit Memo.
     *
     * @param mixed $orderId
     * @param float $amount
     * @param mixed $transactionId
     * @param string $description
     */
    public function createCreditMemo($orderId, $amount, $transactionId, $description);

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
     * Log a message.
     *
     * @param string $level See LogLevel
     * @param string $message Message
     * @param array $context Context
     */
    public function log($level, $message, array $context = []);
}
