<?php

class MobilePayGateway extends Gateway
{
    /**
     * @var string
     */
    public $access_token = ACCESS_TOKEN_MOBILEPAY;

    /**
     * @var string
     */
    public $payee_id = PAYEE_ID_MOBILEPAY;
}
