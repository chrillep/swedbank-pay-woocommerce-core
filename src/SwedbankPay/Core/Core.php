<?php

namespace SwedbankPay\Core;

use SwedbankPay\Api\Client\Client;
use SwedbankPay\Core\Library\Methods\CardInterface;
use SwedbankPay\Core\Library\Methods\CheckoutInterface;
use SwedbankPay\Core\Library\Methods\ConsumerInterface;
use SwedbankPay\Core\Library\Methods\InvoiceInterface;
use SwedbankPay\Core\Library\Methods\SwishInterface;
use SwedbankPay\Core\Library\Methods\Trustly;
use SwedbankPay\Core\Library\Methods\TrustlyInterface;
use SwedbankPay\Core\Library\Methods\Mobilepay;
use SwedbankPay\Core\Library\Methods\MobilepayInterface;
use SwedbankPay\Core\Library\Methods\VippsInterface;
use SwedbankPay\Core\Library\PaymentInfo;
use SwedbankPay\Core\Library\TransactionAction;
use SwedbankPay\Core\Library\OrderAction;
use SwedbankPay\Core\Library\Methods\Card;
use SwedbankPay\Core\Library\Methods\Invoice;
use SwedbankPay\Core\Library\Methods\Swish;
use SwedbankPay\Core\Library\Methods\Vipps;
use SwedbankPay\Core\Library\Methods\Checkout;
use SwedbankPay\Core\Library\Methods\Consumer;

/**
 * Class Core
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @package SwedbankPay\Core
 */
class Core implements
    CoreInterface,
    CardInterface,
    CheckoutInterface,
    InvoiceInterface,
    MobilepayInterface,
    SwishInterface,
    TrustlyInterface,
    VippsInterface,
    ConsumerInterface
{
    use PaymentInfo;
    use Card;
    use Invoice;
    use Swish;
    use Vipps;
    use Trustly;
    use Mobilepay;
    use Checkout;
    use Consumer;
    use TransactionAction;
    use OrderAction;

    /**
     * @var PaymentAdapterInterface
     */
    private $adapter;

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @var Client
     */
    private $client;

    /**
     * Core constructor.
     *
     * @param PaymentAdapterInterface $adapter
     */
    public function __construct(PaymentAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->configuration = $this->getConfiguration();
        $this->client = $this->getClient();
    }

    /**
     * @return Configuration
     */
    private function getConfiguration()
    {
        $default = [
            ConfigurationInterface::DEBUG => true,
            ConfigurationInterface::ACCESS_TOKEN => '',
            ConfigurationInterface::PAYEE_ID => '',
            ConfigurationInterface::PAYEE_NAME => '',
            ConfigurationInterface::MODE => true,
            ConfigurationInterface::AUTO_CAPTURE => false,
            ConfigurationInterface::SUBSITE => null,
            ConfigurationInterface::LANGUAGE => 'en-US',
            ConfigurationInterface::SAVE_CC => false,
            ConfigurationInterface::TERMS_URL => '',
            ConfigurationInterface::LOGO_URL => '',
            ConfigurationInterface::USE_PAYER_INFO => true,
            ConfigurationInterface::USE_CARDHOLDER_INFO => true,
            ConfigurationInterface::REJECT_CREDIT_CARDS => false,
            ConfigurationInterface::REJECT_DEBIT_CARDS => false,
            ConfigurationInterface::REJECT_CONSUMER_CARDS => false,
            ConfigurationInterface::REJECT_CORPORATE_CARDS => false,
            ConfigurationInterface::CHECKOUT_METHOD => null,
        ];

        $result = $this->adapter->getConfiguration();

        return new Configuration(array_merge($default, $result));
    }

    /**
     * @return Client
     * @throws \SwedbankPay\Api\Client\Exception
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    private function getClient()
    {
        $client = new Client();

        $userAgent = $client->getUserAgent();
        if (method_exists($this->adapter, 'getInitiatingSystemUserAgent')) {
            $userAgent .= ' ' . $this->adapter->getInitiatingSystemUserAgent();
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent .= $_SERVER['HTTP_USER_AGENT'];
        }

        $client->setAccessToken($this->configuration->getAccessToken())
            ->setPayeeId($this->configuration->getPayeeId())
            ->setMode($this->configuration->getMode() === true ? Client::MODE_TEST : Client::MODE_PRODUCTION)
            ->setUserAgent($userAgent);

        return $client;
    }

    /**
     * @param mixed $orderId
     *
     * @return Order
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getOrder($orderId)
    {
        $result = $this->adapter->getOrderData($orderId);

        return new Order($result);
    }

    /**
     * @param mixed $orderId
     *
     * @return Order\PlatformUrls
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getPlatformUrls($orderId)
    {
        $result = $this->adapter->getPlatformUrls($orderId);

        return new Order\PlatformUrls($result);
    }

    /**
     * @param mixed $orderId
     *
     * @return Order\RiskIndicator
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getRiskIndicator($orderId)
    {
        $result = $this->adapter->getRiskIndicator($orderId);

        return new Order\RiskIndicator($result);
    }

    /**
     * @param mixed $orderId
     *
     * @return Order\PayeeInfo
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getPayeeInfo($orderId)
    {
        $default = [
            Order\PayeeInfoInterface::PAYEE_ID => $this->configuration->getPayeeId(),
            Order\PayeeInfoInterface::PAYEE_NAME => $this->configuration->getPayeeName(),
            Order\PayeeInfoInterface::ORDER_REFERENCE => $orderId,
            Order\PayeeInfoInterface::PAYEE_REFERENCE => $this->generatePayeeReference($orderId),
        ];

        if ($this->configuration->getSubsite()) {
            $default[Order\PayeeInfoInterface::SUBSITE] = $this->configuration->getSubsite();
        }

        $result = $this->adapter->getPayeeInfo($orderId);

        return new Order\PayeeInfo(array_merge($default, $result));
    }

    /**
     * Log a message.
     *
     * @param string $level See LogLevel
     * @param string $message Message
     * @param array $context Context
     */
    public function log($level, $message, array $context = [])
    {
        if (!$this->configuration->getDebug()) {
            return;
        }

        if (!is_string($message)) {
            $message = var_export($message, true);
        }

        $this->adapter->log($level, $message, $context);
    }

    /**
     * Parse and format error response
     *
     * @param string $responseBody
     *
     * @return string
     */
    public function formatErrorMessage($responseBody)
    {
        $responseBody = json_decode($responseBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $responseBody;
        }

        $message = $responseBody['detail'];
        if (isset($responseBody['problems']) && count($responseBody['problems']) > 0) {
            foreach ($responseBody['problems'] as $problem) {
                // Specify error message for invalid phone numbers. It's such fields like:
                // Payment.Cardholder.Msisdn
                // Payment.Cardholder.HomePhoneNumber
                // Payment.Cardholder.WorkPhoneNumber
                // Payment.Cardholder.BillingAddress.Msisdn
                // Payment.Cardholder.ShippingAddress.Msisdn
                if ((strpos($problem['name'], 'Msisdn') !== false) ||
                    strpos($problem['name'], 'HomePhoneNumber') !== false ||
                    strpos($problem['name'], 'WorkPhoneNumber') !== false
                ) {
                    $message = 'Your phone number format is wrong. Please input with country code, for example like this +46707777777'; //phpcs:ignore

                    break;
                }

                $message .= "\n" . sprintf('%s: %s', $problem['name'], $problem['description']);
            }
        }

        return $message;
    }
}
