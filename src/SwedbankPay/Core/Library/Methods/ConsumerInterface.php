<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface ConsumerInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface ConsumerInterface
{
    const CONSUMERS_URL = '/psp/consumers';

    /**
     * Initiate consumer session.
     *
     * @param string $language
     * @param bool $requireShipping
     * @param array $shippingCountries
     * @return Response
     * @throws Exception
     */
    public function initiateConsumerSession(
        $language,
        $requireShipping,
        $shippingCountries = []
    );
}
