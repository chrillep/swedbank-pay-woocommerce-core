<?php

use SwedbankPay\Core\Api\Response;

class VippsTest extends TestCase
{
    public function testInitiateVippsPayment()
    {
        $this->gateway->currency = 'NOK';
        $result = $this->core->initiateVippsPayment(1, '+4798765432');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertArrayHasKey('operation', $result['payment']);
        $this->assertArrayHasKey('intent', $result['payment']);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('Vipps', $result['payment']['instrument']);
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));
        $this->assertIsString($result->getOperationByRel('redirect-authorization'));

        return $result;
    }

    /**
     * @depends VippsTest::testInitiateVippsPayment
     */
    public function testAbort(Response $response)
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
