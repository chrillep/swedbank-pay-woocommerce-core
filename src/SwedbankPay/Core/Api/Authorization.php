<?php

namespace SwedbankPay\Core\Api;

use SwedbankPay\Core\Data;

/**
 * Class Authorization
 * @package SwedbankPay\Core\Api
 * @todo Add more methods
 */
class Authorization extends Data implements AuthorizationInterface
{
    /**
     * Authorization constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Get Payment Token.
     *
     * @return string
     */
    public function getPaymentToken()
    {
        return $this->getData(self::PAYMENT_TOKEN);
    }

    /**
     * Get Recurrence Token.
     *
     * @return string
     */
    public function getRecurrenceToken()
    {
        return $this->getData(self::RECURRENCE_TOKEN);
    }

    /**
     * Get Masked Pan.
     *
     * @return string
     */
    public function getMaskedPan()
    {
        return $this->getData(self::MASKED_PAN);
    }

    /**
     * Get Card Brand.
     *
     * @return string
     */
    public function getCardBrand()
    {
        return $this->getData(self::CARD_BRAND);
    }

    /**
     * Get Expire Date.
     *
     * @return string
     */
    public function getExpireDate()
    {
        return $this->getData(self::EXPIRY_DATE);
    }
}
