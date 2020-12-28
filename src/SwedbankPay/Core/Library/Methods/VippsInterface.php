<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Exception;

/**
 * Interface VippsInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface VippsInterface
{
    const PRICE_TYPE_VIPPS = 'Vipps';
    const VIPPS_PAYMENTS_URL = '/psp/vipps/payments';

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
}
