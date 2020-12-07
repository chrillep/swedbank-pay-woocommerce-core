<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface MobilepayInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface MobilepayInterface
{
    const PRICE_TYPE_MOBILEPAY = 'MobilePay';
    const PRICE_TYPE_VISA = 'Visa';
    const PRICE_TYPE_MC = 'MC';
    const PRICE_TYPE_MAESTRO = 'Maestro';
    const PRICE_TYPE_DANKORT = 'Dankort';

    const MOBILEPAY_PAYMENT_URL = '/psp/mobilepay/payments';


    /**
     * Initiate Mobilepay Payment
     *
     * @param mixed $orderId
     * @param string $phone Pre-fill phone, optional
     *
     * @return Response
     * @throws Exception
     */
    public function initiateMobilepayPayment($orderId, $phone = '');
}
