<?php

namespace SwedbankPay\Core\Order;

/**
 * Interface PlatformUrlsInterface
 * @package SwedbankPay\Core\Order
 * @method string getCompleteUrl()
 * @method string getCancelUrl()
 * @method string getCallbackUrl()
 * @method string getTermsUrl()
 * @method string getLogoUrl()
 * @method string getPaymentUrl()
 */
interface PlatformUrlsInterface
{
    const COMPLETE_URL = 'complete_url';
    const CANCEL_URL = 'cancel_url';
    const CALLBACK_URL = 'callback_url';
    const TERMS_URL = 'terms_url';
    const LOGO_URL = 'logo_url';
    const PAYMENT_URL = 'payment_url';
}
