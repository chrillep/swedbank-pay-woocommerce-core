<?php

class Gateway
{
    /**
     * @var string
     */
    public $access_token = ACCESS_TOKEN;

    /**
     * @var string
     */
    public $payee_id = PAYEE_ID;

    /**
     * @var string
     */
    public $payee_name = 'Test Merchant';

    /**
     * @var bool
     */
    public $testmode = true;

    /**
     * @var bool
     */
    public $debug = true;

    /**
     * @var bool
     */
    public $auto_capture = true;

    /**
     * @var string
     */
    public $subsite = '';

    /**
     * @var string
     */
    public $language = 'en-US';

    /**
     * @var bool
     */
    public $save_cc = false;

    /**
     * @var string
     */
    public $terms_url = 'https://example.com';

    /**
     * @var string
     */
    public $logo_url = 'https://example.com/logo.png';

    /**
     * @var bool
     */
    public $use_payer_info = true;

    /**
     * @var bool
     */
    public $use_cardholder_info = true;

    /**
     * @var bool
     */
    public $reject_credit_cards = false;

    /**
     * @var bool
     */
    public $reject_debit_cards = false;

    /**
     * @var bool
     */
    public $reject_consumer_cards = false;

    /**
     * @var bool
     */
    public $reject_corporate_cards = false;

    /**
     * @var string
     */
    public $currency = 'EUR';
}
