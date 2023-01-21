<?php

use SwedbankPay\Api\Client\Client;
use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Core;
use SwedbankPay\Core\Configuration;

class CardTest extends TestCase
{
    public function testInitiateCreditCardPayment()
    {
        // Test initialization
        $result = $this->core->initiateCreditCardPayment(1, false, false);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertIsString($result->getOperationByRel('redirect-authorization'));
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));

        return $result;
    }

    /**
     * @depends CardTest::testInitiateCreditCardPayment
     * @param Response $response
     */
    public function testCardAbort(Response $response)
    {
        // Test abort
        $result = $this->core->request(
            'PATCH',
            $response->getOperationByRel('update-payment-abort'),
            [
                'payment' => [
                    'operation' => 'Abort',
                    'abortReason' => 'CancelledByConsumer'
                ]
            ]
        );
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('Aborted', $result['payment']['state']);
    }

    public function testInitiateNewCreditCardPayment() {
        $result = $this->core->initiateVerifyCreditCardPayment(1);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertIsString($result->getOperationByRel('redirect-verification'));
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));

        return $result;
    }

    /**
     * @depends CardTest::testInitiateNewCreditCardPayment
     */
    public function testNewCardAbort(Response $response)
    {
        // Test abort
        $result = $this->core->request(
            'PATCH',
            $response->getOperationByRel('update-payment-abort'),
            [
                'payment' => [
                    'operation' => 'Abort',
                    'abortReason' => 'CancelledByConsumer'
                ]
            ]
        );
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('Aborted', $result['payment']['state']);
    }

    public function testInitiateCreditCardUnscheduledPurchase()
    {
        /** @var Core&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject $coreMock */
        $coreMock = clone $this->core;

        $reflection = new \ReflectionClass($coreMock);
        $adapterProp = $reflection->getProperty('adapter');
        $adapterProp->setAccessible(true);
        $adapterProp->setValue(
            $coreMock,
            $this->adapter
        );

        /** @var Client&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject $clientMock */
        $clientMock = $this->getMockBuilder(Client::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $clientProp = $reflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue(
            $coreMock,
            $clientMock
        );

        /** @var Configuration&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject $configurationMock */
        $configurationMock = $this->getMockBuilder(Configuration::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $map = [
            'getAutoCapture' => false,
            'getSubsite' => 'subsite',
            'getPayeeId' => 'payee-id',
            'getPayeeName' => 'payee-name',
            'getAccessToken' => 'access-token',
            'getMode' => true,
            'getDebug' => false,
        ];

        $configurationMock->expects($this->any())
            ->method('__call')
            ->willReturnCallback(function ($key) use ($map) {
                if (isset($map[$key])) {
                    return $map[$key];
                }

                return false;
            });

        $configurationProp = $reflection->getProperty('configuration');
        $configurationProp->setAccessible(true);
        $configurationProp->setValue(
            $coreMock,
            $configurationMock
        );

        $clientMock->expects($this->once())
                   ->method('getAccessToken')
                   ->willReturn('access-token');

        $clientMock->expects($this->once())
                   ->method('getPayeeId')
                   ->willReturn('payee-id');

        $clientMock->expects($this->once())
                   ->method('request')
                   ->willReturn($clientMock);

        $clientMock->expects($this->once())
                   ->method('getResponseBody')
                   ->willReturn([]);

        $result = $coreMock->initiateCreditCardUnscheduledPurchase(1, 'payment-token');

        $this->assertInstanceOf(Response::class, $result);

        return $result;
    }
}
