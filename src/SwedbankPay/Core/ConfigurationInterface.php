<?php

namespace SwedbankPay\Core;

/**
 * Interface ConfigurationInterface
 * @package SwedbankPay\Core
 * @method bool getDebug()
 * @method string getAccessToken()
 * @method string getPayeeId()
 * @method string getPayeeName()
 * @method bool getMode()
 * @method bool getAutoCapture()
 * @method string getSubsite()
 * @method string getLanguage()
 * @method string getSaveCC()
 * @method string getTermsUrl()
 * @method string getLogoUrl()
 * @method bool getUsePayerInfo()
 * @method bool getUseCardholderInfo()
 * @method bool getRejectCreditCards()
 * @method bool getRejectDebitCards()
 * @method bool getRejectConsumerCards()
 * @method bool getRejectCorporateCards()
 */
interface ConfigurationInterface
{
    const ACCESS_TOKEN = 'access_token';
    const PAYEE_ID = 'payee_id';
    const PAYEE_NAME = 'payee_name';
    const MODE = 'mode';
    const AUTO_CAPTURE = 'auto_capture';
    const SUBSITE = 'subsite';
    const DEBUG = 'debug';
    const LANGUAGE = 'language';
    const SAVE_CC = 'save_cc';
    const TERMS_URL = 'terms_url';
    const LOGO_URL = 'logo_url';
    const USE_PAYER_INFO = 'use_payer_info';
    const USE_CARDHOLDER_INFO = 'use_cardholder_info';
    const CHECKOUT_METHOD = 'method';

    const REJECT_CREDIT_CARDS = 'reject_credit_cards';
    const REJECT_DEBIT_CARDS = 'reject_debit_cards';
    const REJECT_CONSUMER_CARDS = 'reject_consumer_cards';
    const REJECT_CORPORATE_CARDS = 'reject_corporate_cards';

    /**
     * Checkout Methods
     */
    const METHOD_DIRECT = 'direct';
    const METHOD_REDIRECT = 'redirect';
    const METHOD_SEAMLESS = 'seamless';
}
