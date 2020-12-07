<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface CustomerInterface
 * @package SwedbankPay\Core
 */
interface TrustlyInterface
{
    const PRICE_TYPE_TRUSTLY = 'Trustly';
    const TRUSTLY_PAYMENTS_URL = '/psp/trustly/payments';

    /**
     * Initiate Trustly Payment
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function initiateTrustlyPayment($orderId);
}
