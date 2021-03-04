<?php

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Core;

class MobilepayTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('ACCESS_TOKEN_MOBILEPAY') ||
            ACCESS_TOKEN_MOBILEPAY === '<access_token>') {
            $this->fail('ACCESS_TOKEN_MOBILEPAY not configured in INI file or environment variable.');
        }

        if (!defined('PAYEE_ID_MOBILEPAY') ||
            PAYEE_ID === '<payee_id>') {
            $this->fail('PAYEE_ID_MOBILEPAY not configured in INI file or environment variable.');
        }

        $this->gateway = new MobilePayGateway();
        $this->adapter = new Adapter($this->gateway);
        $this->core = new Core($this->adapter);
    }

    public function testInitiateMobilepayPayment()
    {
        $this->gateway->currency = 'DKK';
        $result = $this->core->initiateMobilepayPayment(1, '+4581555853');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertArrayHasKey('operation', $result['payment']);
        $this->assertArrayHasKey('intent', $result['payment']);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('MobilePay', $result['payment']['instrument']);
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));
        $this->assertIsString($result->getOperationByRel('redirect-authorization'));

        return $result;
    }

    /**
     * @depends MobilepayTest::testInitiateMobilepayPayment
     * @param Response $response
     */
    public function testMobilepayPaymentPaymentPage(Response $response)
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
}
