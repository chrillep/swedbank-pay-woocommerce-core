<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\ConfigurationInterface;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;
use SwedbankPay\Core\Order;

use SwedbankPay\Api\Service\Payment\Resource\Collection\PricesCollection;
use SwedbankPay\Api\Service\Payment\Resource\Collection\Item\PriceItem;
use SwedbankPay\Api\Service\Payment\Resource\Request\Metadata;
use SwedbankPay\Api\Service\MobilePay\Request\Purchase;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentPayeeInfo;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentPrefillInfo;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentUrl;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\Payment;
use SwedbankPay\Api\Service\MobilePay\Resource\Request\PaymentObject;
use SwedbankPay\Api\Service\Data\ResponseInterface as ResponseServiceInterface;

/**
 * Trait Mobilepay
 * @package SwedbankPay\Core\Library\Methods
 */
trait Mobilepay
{
    /**
     * Initiate Mobilepay Payment
     *
     * @param mixed $orderId
     * @param string $phone Pre-fill phone, optional
     *
     * @return Response
     * @throws Exception
     */
    public function initiateMobilepayPayment($orderId, $phone = '')
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        /** @var Order\PlatformUrls $urls */
        $urls = $this->getPlatformUrls($orderId);

        $url = new PaymentUrl();
        $url->setCompleteUrl($urls->getCompleteUrl())
            ->setCancelUrl($urls->getCancelUrl())
            ->setCallbackUrl($urls->getCallbackUrl())
            ->setHostUrls($urls->getHostUrls());

        if ($this->configuration->getData(ConfigurationInterface::CHECKOUT_METHOD) ===
            ConfigurationInterface::METHOD_SEAMLESS
        ) {
            $url->setPaymentUrl($urls->getPaymentUrl());
        }

        $payeeInfo = new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray());

        $prefillInfo = new PaymentPrefillInfo();
        if (!empty($phone)) {
            $prefillInfo->setMsisdn($phone);
        }

        $price = new PriceItem();
        $price->setType(MobilepayInterface::PRICE_TYPE_MOBILEPAY)
            ->setAmount($order->getAmountInCents())
            ->setVatAmount($order->getVatAmountInCents());

        $prices = new PricesCollection();
        $prices->addItem($price);

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $payment = new Payment();
        $payment->setOperation(self::OPERATION_PURCHASE)
            ->setIntent(self::INTENT_AUTHORIZATION)
            ->setCurrency($order->getCurrency())
            ->setDescription($order->getDescription())
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo($payeeInfo)
            ->setPrefillInfo($prefillInfo)
            ->setPrices($prices)
            ->setMetadata($metadata)
            ->setPayerReference($order->getPayerReference());

        $paymentObject = new PaymentObject();
        $paymentObject->setPayment($payment)
            ->setShoplogoUrl($urls->getLogoUrl());

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
        } catch (\Exception $e) {
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
}
