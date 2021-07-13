<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;

trait Consumer
{
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
    ) {
        $params = [
            'operation' => 'initiate-consumer-session',
            'language' => $language,
            'shippingAddressRestrictedToCountryCodes' => $shippingCountries,
            'requireShippingAddress' => $requireShipping
        ];

        try {
            $result = $this->request('POST', self::CONSUMERS_URL, $params);
        } catch (\SwedbankPay\Core\Exception $e) {
            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage(), $e->getCode(), null, $e->getProblems());
        } catch (\Exception $e) {
            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }

        return $result;
    }
}
