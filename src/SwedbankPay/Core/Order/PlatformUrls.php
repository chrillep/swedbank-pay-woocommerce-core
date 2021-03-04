<?php

namespace SwedbankPay\Core\Order;

use SwedbankPay\Core\Data;

/**
 * Class PlatformUrls
 * @package SwedbankPay\Core\Order
 * @method string getCompleteUrl()
 * @method string getCancelUrl()
 * @method string getCallbackUrl()
 * @method string getTermsUrl()
 * @method string getLogoUrl()
 * @method string getPaymentUrl()
 */
class PlatformUrls extends Data implements PlatformUrlsInterface
{
    /**
     * PlatformUrls constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Get urls where hosts
     *
     * @return array
     */
    public function getHostUrls()
    {
        $urls = [];

        foreach ($this->getData() as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $parsed = parse_url($url);
                $urls[] = sprintf('%s://%s', $parsed['scheme'], $parsed['host']);
            }
        }

        return array_values(array_unique($urls));
    }
}
