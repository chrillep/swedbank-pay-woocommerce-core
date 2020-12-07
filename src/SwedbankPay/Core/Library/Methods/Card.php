<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;
use SwedbankPay\Core\Order;

trait Card
{
    /**
     * Initiate a Credit Card Payment
     *
     * @param mixed $orderId
     * @param bool $generateToken
     * @param string $paymentToken
     *
     * @return Response
     * @throws Exception
     */
    public function initiateCreditCardPayment($orderId, $generateToken, $paymentToken)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $urls = $this->getPlatformUrls($orderId);

        // Process payment
        $params = [
            'payment' => [
                'operation' => self::OPERATION_PURCHASE,
                'intent' => $this->configuration->getAutoCapture() ? self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION,
                'currency' => $order->getCurrency(),
                'prices' => [
                    [
                        'type' => self::TYPE_CREDITCARD,
                        'amount' => $order->getAmountInCents(),
                        'vatAmount' => $order->getVatAmountInCents(),
                    ]
                ],
                'description' => $order->getDescription(),
                'payerReference' => $order->getPayerReference(),
                'generatePaymentToken' => $generateToken,
                'generateRecurrenceToken' => $generateToken,
                'pageStripdown' => false,
                'userAgent' => $order->getHttpUserAgent(),
                'language' => $order->getLanguage(),
                'urls' => [
                    'hostUrls' => $urls->getHostUrls(),
                    'completeUrl' => $urls->getCompleteUrl(),
                    'cancelUrl' => $urls->getCancelUrl(),
                    'callbackUrl' => $urls->getCallbackUrl(),
                    'termsOfServiceUrl' => $urls->getTermsUrl(),
                    'logoUrl' => $urls->getLogoUrl(),
                ],
                'payeeInfo' => $this->getPayeeInfo($orderId)->toArray(),
                'riskIndicator' => $this->getRiskIndicator($orderId)->toArray(),
                'creditCard' => [
                    'rejectCreditCards' => $this->configuration->getRejectCreditCards(),
                    'rejectDebitCards' => $this->configuration->getRejectDebitCards(),
                    'rejectConsumerCards' => $this->configuration->getRejectConsumerCards(),
                    'rejectCorporateCards' => $this->configuration->getRejectCorporateCards()
                ],
                'prefillInfo' => [
                    'msisdn' => $order->getBillingPhone()
                ],
                'metadata' => [
                    'order_id' => $order->getOrderId()
                ],
            ]
        ];

        if ($this->configuration->getUseCardholderInfo()) {
            $params['payment']['cardholder'] = [
                'firstName'       => $order->getBillingFirstName(),
                'lastName'        => $order->getBillingLastName(),
                'email'           => $order->getBillingEmail(),
                'msisdn'          => $order->getBillingPhone(),
                'homePhoneNumber' => $order->getBillingPhone(),
                'workPhoneNumber' => $order->getBillingPhone(),
            ];

            if ($this->configuration->getUsePayerInfo()) {
                $params['payment']['cardholder']['billingAddress'] = [
                    'firstName'     => $order->getBillingFirstName(),
                    'lastName'      => $order->getBillingLastName(),
                    'email'         => $order->getBillingEmail(),
                    'msisdn'        => $order->getBillingPhone(),
                    'streetAddress' => implode(', ',
                        [$order->getBillingAddress1(), $order->getBillingAddress2()]
                    ),
                    'coAddress'     => '',
                    'city'          => $order->getBillingCity(),
                    'zipCode'       => $order->getBillingPostcode(),
                    'countryCode'   => $order->getBillingCountryCode()
                ];

                // Add shipping address if needs
                if ($order->needsShipping()) {
                    $info['shippingAddress'] = [
                        'firstName'     => $order->getShippingFirstName(),
                        'lastName'      => $order->getShippingLastName(),
                        'email'         => $order->getShippingEmail(),
                        'msisdn'        => $order->getShippingPhone(),
                        'streetAddress' => implode(', ',
                            [$order->getShippingAddress1(), $order->getShippingAddress2()]),
                        'coAddress'     => '',
                        'city'          => $order->getShippingCity(),
                        'zipCode'       => $order->getShippingPostcode(),
                        'countryCode'   => $order->getShippingCountryCode()
                    ];
                }
            }
        }

        if ($paymentToken) {
            $params['payment']['paymentToken'] = $paymentToken;
            $params['payment']['generatePaymentToken'] = false;
            $params['payment']['generateRecurrenceToken'] = false;
        }

        try {
            $result = $this->request('POST', '/psp/creditcard/payments', $params);
        } catch (\Exception $e) {
            $this->log(LogLevel::DEBUG, sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage()));

            throw new Exception($e->getMessage());
        }

        return $result;
    }

    /**
     * Initiate Verify Credit Card Payment
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     */
    public function initiateVerifyCreditCardPayment($orderId)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $urls = $this->getPlatformUrls($orderId);

        $params = [
            'payment' => [
                'operation' => self::OPERATION_VERIFY,
                'currency' => $order->getCurrency(),
                'description' => 'Verification of Credit Card',
                'payerReference' => $order->getPayerReference(),
                'generatePaymentToken' => true,
                'generateRecurrenceToken' => true,
                'pageStripdown' => false,
                'userAgent' => $order->getHttpUserAgent(),
                'language' => $order->getLanguage(),
                'urls' => [
                    'completeUrl' => $urls->getCompleteUrl(),
                    'cancelUrl' => $urls->getCancelUrl(),
                    'callbackUrl' => $urls->getCallbackUrl(),
                    'termsOfServiceUrl' => $urls->getTermsUrl(),
                    'logoUrl' => $urls->getLogoUrl(),
                ],
                'payeeInfo' => $this->getPayeeInfo($orderId)->toArray(),
                'riskIndicator' => $this->getRiskIndicator($orderId)->toArray(),
                'creditCard' => [
                    'rejectCreditCards' => $this->configuration->getRejectCreditCards(),
                    'rejectDebitCards' => $this->configuration->getRejectDebitCards(),
                    'rejectConsumerCards' => $this->configuration->getRejectConsumerCards(),
                    'rejectCorporateCards' => $this->configuration->getRejectCorporateCards()
                ],
                'metadata' => [
                    'order_id' => $order->getOrderId()
                ],
            ]
        ];

        if ($this->configuration->getUseCardholderInfo()) {
            $params['payment']['cardholder'] = $order->getCardHolderInformation();
        }

        try {
            $result = $this->request('POST', '/psp/creditcard/payments', $params);
        } catch (\Exception $e) {
            $this->log(LogLevel::DEBUG, sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage()));

            throw new Exception($e->getMessage());
        }

        return $result;
    }

    /**
     * Initiate a CreditCard Recurrent Payment
     *
     * @param mixed $orderId
     * @param string $recurrenceToken
     * @param string|null $paymentToken
     *
     * @return Response
     * @throws \Exception
     */
    public function initiateCreditCardRecur($orderId, $recurrenceToken, $paymentToken = null)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $params = [
            'payment' => [
                'operation' => self::OPERATION_RECUR,
                'intent' => $this->configuration->getAutoCapture() ? self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION,
                'currency' => $order->getCurrency(),
                'amount' => $order->getAmountInCents(),
                'vatAmount' => $order->getVatAmountInCents(),
                'description' => $order->getDescription(),
                'payerReference' => $order->getPayerReference(),
                'userAgent' => $order->getHttpUserAgent(),
                'language' => $order->getLanguage(),
                'urls' => [
                    'callbackUrl' => $this->getPlatformUrls($orderId)->getCallbackUrl()
                ],
                'payeeInfo' => $this->getPayeeInfo($orderId)->toArray(),
                'riskIndicator' => $this->getRiskIndicator($orderId)->toArray(),
                'metadata' => [
                    'order_id' => $orderId
                ],
            ]
        ];

        // Use Recurrence Token if it's exist
        if (!empty($recurrenceToken)) {
            $params['payment']['recurrenceToken'] = $recurrenceToken;
        } else {
            $params['payment']['paymentToken'] = $paymentToken;
        }

        try {
            $result = $this->request('POST', '/psp/creditcard/payments', $params);
        } catch (\Exception $e) {
            $this->log(LogLevel::DEBUG, sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage()));

            throw $e;
        }

        return $result;
    }

    /**
     * Initiate a CreditCard Unscheduled Purchase
     *
     * @param mixed $orderId
     * @param string $recurrenceToken
     * @param string|null $paymentToken
     *
     * @return Response
     * @throws \Exception
     */
    public function initiateCreditCardUnscheduledPurchase($orderId, $recurrenceToken, $paymentToken = null)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $params = [
            'payment' => [
                'operation' => self::OPERATION_UNSCHEDULED_PURCHASE,
                'intent' => $this->configuration->getAutoCapture() ? self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION,
                'currency' => $order->getCurrency(),
                'amount' => $order->getAmountInCents(),
                'vatAmount' => $order->getVatAmountInCents(),
                'description' => $order->getDescription(),
                'payerReference' => $order->getPayerReference(),
                'userAgent' => $order->getHttpUserAgent(),
                'language' => $order->getLanguage(),
                'urls' => [
                    'callbackUrl' => $this->getPlatformUrls($orderId)->getCallbackUrl()
                ],
                'payeeInfo' => $this->getPayeeInfo($orderId)->toArray(),
                'riskIndicator' => $this->getRiskIndicator($orderId)->toArray(),
                'metadata' => [
                    'order_id' => $orderId
                ],
            ]
        ];

        // Use Recurrence Token if it's exist
        if (!empty($recurrenceToken)) {
            $params['payment']['recurrenceToken'] = $recurrenceToken;
        } else {
            $params['payment']['paymentToken'] = $paymentToken;
        }

        try {
            $result = $this->request('POST', '/psp/creditcard/payments', $params);
        } catch (\Exception $e) {
            $this->log(LogLevel::DEBUG, sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage()));

            throw $e;
        }

        return $result;
    }
}
