<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\ConfigurationInterface;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;
use SwedbankPay\Core\Order;

use SwedbankPay\Api\Client\Exception as ClientException;
use SwedbankPay\Api\Service\Creditcard\Request\Purchase;
use SwedbankPay\Api\Service\Creditcard\Request\Verify;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPurchaseCreditcard;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPurchaseObject;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentUrl;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentVerifyCreditcard;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentVerify;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentVerifyObject;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPurchase;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentRecur;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentRecurObject;
use SwedbankPay\Api\Service\Payment\Resource\Collection\PricesCollection;
use SwedbankPay\Api\Service\Payment\Resource\Collection\Item\PriceItem;
use SwedbankPay\Api\Service\Payment\Resource\Request\Metadata;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPayeeInfo;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPrefillInfo;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentRiskIndicator;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentCardholder;
use SwedbankPay\Api\Service\Creditcard\Resource\Request\CardholderAddress;
use SwedbankPay\Api\Service\Data\ResponseInterface as ResponseServiceInterface;

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function initiateCreditCardPayment($orderId, $generateToken, $paymentToken)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $urls = $this->getPlatformUrls($orderId);

        $url = new PaymentUrl();
        $url->setCompleteUrl($urls->getCompleteUrl())
            ->setCancelUrl($urls->getCancelUrl())
            ->setCallbackUrl($urls->getCallbackUrl())
            ->setLogoUrl($urls->getLogoUrl())
            ->setTermsOfService($urls->getTermsUrl())
            ->setHostUrls($urls->getHostUrls());

        if ($this->configuration->getData(ConfigurationInterface::CHECKOUT_METHOD) ===
            ConfigurationInterface::METHOD_SEAMLESS
        ) {
            $url->setPaymentUrl($urls->getPaymentUrl());
        }

        $price = new PriceItem();
        $price->setType(self::TYPE_CREDITCARD)
              ->setAmount($order->getAmountInCents())
              ->setVatAmount($order->getVatAmountInCents());

        $prices = new PricesCollection();
        $prices->addItem($price);

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $payment = new PaymentPurchase();
        $payment
            ->setOperation(self::OPERATION_PURCHASE)
            ->setIntent(
                $this->configuration->getAutoCapture() ?
                    self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION
            )
            ->setCurrency($order->getCurrency())
            ->setPrices($prices)
            ->setDescription($order->getDescription())
            ->setPayerReference($order->getPayerReference())
            ->setGeneratePaymentToken($generateToken)
            ->setGenerateRecurrenceToken($generateToken)
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo(
                new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray())
            )
            ->setRiskIndicator(
                new PaymentRiskIndicator($this->getRiskIndicator($orderId)->toArray())
            )
            ->setPrefillInfo(
                (new PaymentPrefillInfo())
                    ->setMsisdn($order->getBillingPhone())
            )
            ->setMetadata($metadata);

        // Add payment token
        if ($paymentToken) {
            $payment
                ->setPaymentToken($paymentToken)
                ->setGeneratePaymentToken(false)
                ->setGenerateRecurrenceToken(false);
        }

        // Add Cardholder info
        $payment->setCardholder($this->getCardHolderInformation($order));

        $paymentObject = new PaymentPurchaseObject();
        $paymentObject->setPayment($payment);

        // Add Credit Card
        $creditCard = new PaymentPurchaseCreditcard();
        $creditCard
            ->setRejectCreditCards($this->configuration->getRejectCreditCards())
            ->setRejectDebitCards($this->configuration->getRejectDebitCards())
            ->setRejectConsumerCards($this->configuration->getRejectConsumerCards())
            ->setRejectCorporateCards($this->configuration->getRejectCorporateCards());
        $paymentObject->setCreditCard($creditCard);

        // Process payment object
        $paymentObject = $this->adapter->processPaymentObject($paymentObject, $orderId);

        $purchaseRequest = new Purchase($paymentObject);
        $purchaseRequest->setClient($this->client);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $purchaseRequest->send();

            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            return new Response($responseService->getResponseData());
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($this->formatErrorMessage($purchaseRequest->getClient()->getResponseBody()));
        }
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

        $url = new PaymentUrl();
        $url->setCompleteUrl($urls->getCompleteUrl())
            ->setCancelUrl($urls->getCancelUrl())
            ->setCallbackUrl($urls->getCallbackUrl())
            ->setLogoUrl($urls->getLogoUrl())
            ->setTermsOfService($urls->getTermsUrl())
            ->setHostUrls($urls->getHostUrls());

        if ($this->configuration->getData(ConfigurationInterface::CHECKOUT_METHOD) ===
            ConfigurationInterface::METHOD_SEAMLESS
        ) {
            $url->setPaymentUrl($urls->getPaymentUrl());
        }

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $payment = new PaymentVerify();
        $payment
            ->setOperation(self::OPERATION_VERIFY)
            ->setIntent(
                $this->configuration->getAutoCapture() ?
                    self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION
            )
            ->setCurrency($order->getCurrency())
            ->setDescription('Verification of Credit Card')
            ->setPayerReference($order->getPayerReference())
            ->setGeneratePaymentToken(true)
            ->setGenerateRecurrenceToken(true)
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo(
                new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray())
            )
            ->setPrefillInfo(
                (new PaymentPrefillInfo())->setMsisdn($order->getBillingPhone())
            )
            ->setMetadata($metadata);

        $paymentObject = new PaymentVerifyObject();
        $paymentObject->setPayment($payment);

        // Add Credit Card
        $creditCard = new PaymentVerifyCreditcard();
        $creditCard
            ->setRejectCreditCards($this->configuration->getRejectCreditCards())
            ->setRejectDebitCards($this->configuration->getRejectDebitCards())
            ->setRejectConsumerCards($this->configuration->getRejectConsumerCards())
            ->setRejectCorporateCards($this->configuration->getRejectCorporateCards());

        $paymentObject->setCreditCard($creditCard);

        // Process payment object
        $paymentObject = $this->adapter->processPaymentObject($paymentObject, $orderId);

        $verifyRequest = new Verify($paymentObject);
        $verifyRequest->setClient($this->client);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $verifyRequest->send();

            $this->log(
                LogLevel::DEBUG,
                $verifyRequest->getClient()->getDebugInfo()
            );

            return new Response($responseService->getResponseData());
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $verifyRequest->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($this->formatErrorMessage($verifyRequest->getClient()->getResponseBody()));
        }
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function initiateCreditCardRecur($orderId, $recurrenceToken, $paymentToken = null)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $urls = $this->getPlatformUrls($orderId);

        $url = new PaymentUrl();
        $url->setCallbackUrl($urls->getCallbackUrl());

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $payment = new PaymentRecur();
        $payment
            ->setOperation(self::OPERATION_RECUR)
            ->setIntent(
                $this->configuration->getAutoCapture() ? self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION
            )
            ->setCurrency($order->getCurrency())
            ->setAmount($order->getAmountInCents())
            ->setVatAmount($order->getVatAmountInCents())
            ->setDescription($order->getDescription())
            ->setPayerReference($order->getPayerReference())
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo(
                new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray())
            )
            ->setRiskIndicator(
                new PaymentRiskIndicator($this->getRiskIndicator($orderId)->toArray())
            )
            ->setPrefillInfo(
                (new PaymentPrefillInfo())->setMsisdn($order->getBillingPhone())
            )
            ->setMetadata($metadata);

        // Use Recurrence Token if it's exist
        if (!empty($recurrenceToken)) {
            $payment->setRecurrenceToken($recurrenceToken);
        } else {
            $payment->setPaymentToken($paymentToken);
        }

        $paymentObject = new PaymentRecurObject();
        $paymentObject->setPayment($payment);

        // Process payment object
        $paymentObject = $this->adapter->processPaymentObject($paymentObject, $orderId);

        $purchaseRequest = new Purchase($paymentObject);
        $purchaseRequest->setClient($this->client);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $purchaseRequest->send();

            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            return new Response($responseService->getResponseData());
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($this->formatErrorMessage($purchaseRequest->getClient()->getResponseBody()));
        }
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function initiateCreditCardUnscheduledPurchase($orderId, $recurrenceToken, $paymentToken = null)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $urls = $this->getPlatformUrls($orderId);

        $url = new PaymentUrl();
        $url->setCallbackUrl($urls->getCallbackUrl());

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $payment = new PaymentRecur();
        $payment
            ->setOperation(self::OPERATION_UNSCHEDULED_PURCHASE)
            ->setIntent(
                $this->configuration->getAutoCapture() ?
                    self::INTENT_AUTOCAPTURE : self::INTENT_AUTHORIZATION
            )
            ->setCurrency($order->getCurrency())
            ->setAmount($order->getAmountInCents())
            ->setVatAmount($order->getVatAmountInCents())
            ->setDescription($order->getDescription())
            ->setPayerReference($order->getPayerReference())
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo(
                new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray())
            )
            ->setRiskIndicator(
                new PaymentRiskIndicator($this->getRiskIndicator($orderId)->toArray())
            )
            ->setPrefillInfo(
                (new PaymentPrefillInfo())->setMsisdn($order->getBillingPhone())
            )
            ->setMetadata($metadata);

        // Use Recurrence Token if it's exist
        if (!empty($recurrenceToken)) {
            $payment->setRecurrenceToken($recurrenceToken);
        } else {
            $payment->setPaymentToken($paymentToken);
        }

        $paymentObject = new PaymentRecurObject();
        $paymentObject->setPayment($payment);

        // Process payment object
        $paymentObject = $this->adapter->processPaymentObject($paymentObject, $orderId);

        $purchaseRequest = new Purchase($paymentObject);
        $purchaseRequest->setClient($this->client);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $purchaseRequest->send();

            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            return new Response($responseService->getResponseData());
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $purchaseRequest->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($this->formatErrorMessage($purchaseRequest->getClient()->getResponseBody()));
        }
    }

    /**
     * @param Order $order
     *
     * @return PaymentCardholder|null
     */
    private function getCardHolderInformation(Order $order)
    {
        // Add Cardholder info
        if ($this->configuration->getUseCardholderInfo()) {
            // Add basic cardholder information
            $cardHolder = new PaymentCardholder();
            $cardHolder
                ->setFirstName($order->getBillingFirstName())
                ->setLastName($order->getBillingLastName())
                ->setEmail($order->getBillingEmail())
                ->setMsisdn($order->getBillingPhone())
                ->setHomePhoneNumber($order->getBillingPhone())
                ->setWorkPhoneNumber($order->getBillingPhone());

            if ($this->configuration->getUsePayerInfo()) {
                // Add billing info
                $cardHolder->setBillingAddress(
                    (new CardholderAddress)
                        ->setFirstName($order->getBillingFirstName())
                        ->setLastName($order->getBillingLastName())
                        ->setEmail($order->getBillingEmail())
                        ->setMsisdn($order->getBillingPhone())
                        ->setStreetAddress(implode(
                            ', ',
                            [$order->getBillingAddress1(), $order->getBillingAddress2()]
                        ))
                        ->setCoAddress('')
                        ->setCity($order->getBillingCity())
                        ->setZipCode($order->getBillingPostcode())
                        ->setCountryCode($order->getBillingCountryCode())
                );

                // Add shipping address if needs
                if ($order->needsShipping()) {
                    $cardHolder->setShippingAddress(
                        (new CardholderAddress)
                            ->setFirstName($order->getShippingFirstName())
                            ->setLastName($order->getShippingLastName())
                            ->setEmail($order->getShippingEmail())
                            ->setMsisdn($order->getShippingPhone())
                            ->setStreetAddress(implode(
                                ', ',
                                [$order->getShippingAddress1(), $order->getShippingAddress2()]
                            ))
                            ->setCoAddress('')
                            ->setCity($order->getShippingCity())
                            ->setZipCode($order->getShippingPostcode())
                            ->setCountryCode($order->getShippingCountryCode())
                    );
                }
            }

            // @todo Add $cardHolder->setAccountInfo()

            return $cardHolder;
        }

        return null;
    }
}
