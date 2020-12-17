<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;
use SwedbankPay\Core\Order;

use SwedbankPay\Api\Service\Payment\Resource\Collection\PricesCollection;
use SwedbankPay\Api\Service\Payment\Resource\Collection\Item\PriceItem;
use SwedbankPay\Api\Service\Payment\Resource\Request\Metadata;
use SwedbankPay\Api\Service\Swish\Request\Purchase;
use SwedbankPay\Api\Service\Swish\Resource\Request\PaymentPayeeInfo;
use SwedbankPay\Api\Service\Swish\Resource\Request\PaymentPrefillInfo;
use SwedbankPay\Api\Service\Swish\Resource\Request\PaymentUrl;
use SwedbankPay\Api\Service\Swish\Resource\Request\Payment;
use SwedbankPay\Api\Service\Swish\Resource\Request\SwishPaymentObject as PaymentObject;
use SwedbankPay\Api\Service\Swish\Resource\Request\PaymentSwish;
use SwedbankPay\Api\Service\Data\ResponseInterface as ResponseServiceInterface;

trait Swish
{
    /**
     * Initiate Swish Payment
     *
     * @param mixed $orderId
     * @param string $phone
     * @param bool $ecomOnlyEnabled
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function initiateSwishPayment($orderId, $phone, $ecomOnlyEnabled = true)
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

        $payeeInfo = new PaymentPayeeInfo($this->getPayeeInfo($orderId)->toArray());

        $prefillInfo = new PaymentPrefillInfo([
            'msisdn' => $phone,
        ]);

        $price = new PriceItem();
        $price->setType(SwishInterface::PRICE_TYPE_SWISH)
            ->setAmount($order->getAmountInCents())
            ->setVatAmount($order->getVatAmountInCents());

        $prices = new PricesCollection();
        $prices->addItem($price);

        $metadata = new Metadata();
        $metadata->setData('order_id', $order->getOrderId());

        $swish = new PaymentSwish();
        $swish->setEcomOnlyEnabled($ecomOnlyEnabled);

        $payment = new Payment();
        $payment->setOperation(self::OPERATION_PURCHASE)
            ->setIntent(self::INTENT_SALE)
            ->setCurrency($order->getCurrency())
            ->setDescription($order->getDescription())
            ->setUserAgent($order->getHttpUserAgent())
            ->setLanguage($order->getLanguage())
            ->setUrls($url)
            ->setPayeeInfo($payeeInfo)
            ->setPrefillInfo($prefillInfo)
            ->setPrices($prices)
            ->setMetadata($metadata)
            ->setSwish($swish);

        $paymentObject = new PaymentObject();
        $paymentObject->setPayment($payment);

        $purchaseRequest = new Purchase($paymentObject);
        $purchaseRequest->setClient($this->client);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $purchaseRequest->send();

            return new Response($responseService->getResponseData());
        } catch (\Exception $e) {
            $this->log(
                LogLevel::DEBUG,
                sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }
    }

    /**
     * initiate Swish Payment Direct
     *
     * @param string $saleHref
     * @param string $phone
     *
     * @return mixed
     * @throws Exception
     */
    public function initiateSwishPaymentDirect($saleHref, $phone)
    {
        $params = [
            'transaction' => [
                'msisdn' => $phone
            ]
        ];

        try {
            $result = $this->request('POST', $saleHref, $params);
        } catch (\Exception $e) {
            $this->log(
                LogLevel::DEBUG,
                sprintf('%s::%s: API Exception: %s', __CLASS__, __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }

        return $result;
    }
}
