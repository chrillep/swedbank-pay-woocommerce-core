<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface CheckoutInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface CheckoutInterface
{
    const PAYMENTORDER_DELETE_TOKEN_URL = '/psp/paymentorders/payerownedtokens/%s';

    /**
     * Initiate Payment Order Purchase.
     *
     * @param mixed $orderId
     * @param string|null $consumerProfileRef
     * @param bool $generateRecurrenceToken
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.LongVariable)
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
     * Initiate Payment Order Unscheduled Payment.
     *
     * @param mixed $orderId
     * @param string $unscheduledToken
     *
     * @return Response
     * @throws \Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function initiatePaymentOrderUnscheduledPurchase($orderId, $unscheduledToken);

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
     * Extract and save tokens.
     *
     * @param mixed $orderId
     *
     * @return void
     * @throws Exception
     */
    public function savePaymentOrderTokens($orderId);

    /**
     * Capture Checkout.
     *
     * @param mixed $orderId
     * @param \SwedbankPay\Core\OrderItem[] $items
     *
     * @return Response
     * @throws Exception
     */
    public function captureCheckout($orderId, array $items = []);

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
     * @param \SwedbankPay\Core\OrderItem[] $items
     *
     * @return Response
     * @throws Exception
     */
    public function refundCheckout($orderId, array $items = []);

    /**
     * Delete Token.
     *
     * @param string $paymentToken
     *
     * @return void
     * @throws Exception
     */
    public function deletePaymentToken($paymentToken);
}
