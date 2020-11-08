<?php

namespace SwedbankPay\Core;

/**
 * Interface PaymentAdapterInterface
 * @package SwedbankPay\Core
 */
interface PaymentAdapterInterface
{
	/**
	 * Payment Methods
	 */
	const METHOD_CHECKOUT = 'checkout';
	const METHOD_CC = 'cc';
	const METHOD_INVOICE = 'invoice';
	const METHOD_MOBILEPAY = 'mobilepay';
	const METHOD_SWISH = 'swish';
	const METHOD_TRUSTLY = 'trustly';
	const METHOD_VIPPS = 'vipps';

    /**
     * Log a message.
     *
     * @param $level
     * @param $message
     * @param array $context
     *
     * @see WC_Log_Levels
     */
    public function log($level, $message, array $context = []);

    /**
     * Get Adapter Configuration.
     *
     * @return array
     */
    public function getConfiguration();

    /**
     * Get Platform Urls of Actions of Order (complete, cancel, callback urls).
     *
     * @param mixed $orderId
     *
     * @return array
     */
    public function getPlatformUrls($orderId);

    /**
     * Get Order Data.
     *
     * @param mixed $orderId
     *
     * @return array
     */
    public function getOrderData($orderId);

    /**
     * Get Risk Indicator of Order.
     *
     * @param mixed $orderId
     *
     * @return array
     */
    public function getRiskIndicator($orderId);

    /**
     * Get Payee Info of Order.
     *
     * @param mixed $orderId
     *
     * @return array
     */
    public function getPayeeInfo($orderId);

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
	 * @param $order_id
	 *
	 * @see wc_get_order_statuses()
	 * @return string
	 * @throws Exception
	 */
	public function getOrderStatus($order_id);

    /**
     * Update Order Status.
     *
     * @param mixed $orderId
     * @param string $status
     * @param string|null $message
     * @param mixed|null $transactionId
     */
    public function updateOrderStatus($orderId, $status, $message = null, $transactionId = null);

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
     * Save Transaction data.
     *
     * @param mixed $orderId
     * @param array $transactionData
     */
    public function saveTransaction($orderId, array $transactionData = []);

    /**
     * Find for Transaction.
     *
     * @param $field
     * @param $value
     *
     * @return array
     */
    public function findTransaction($field, $value);

    /**
     * Save Payment Token.
     *
     * @param mixed $customerId
     * @param string $paymentToken
     * @param string $recurrenceToken
     * @param string $cardBrand
     * @param string $maskedPan
     * @param string $expiryDate
     * @param mixed|null $orderId
     */
    public function savePaymentToken(
        $customerId,
        $paymentToken,
        $recurrenceToken,
        $cardBrand,
        $maskedPan,
        $expiryDate,
        $orderId = null
    );
}
