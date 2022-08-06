<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface InvoiceInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface InvoiceInterface
{
    const INVOICE_PAYMENTS_URL = '/psp/invoice/payments';

    const PRICE_TYPE_INVOICE = 'Invoice';

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
     * @param \SwedbankPay\Core\OrderItem[] $items
     *
     * @return Response
     * @throws Exception
     */
    public function captureInvoice($orderId, array $items = []);

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
}
