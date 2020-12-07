<?php

use SwedbankPay\Core\Api\Response;

class SwishTest extends TestCase
{
    public function testInitiateSwishPayment()
    {
        $this->gateway->currency = 'SEK';
        $result = $this->core->initiateSwishPayment(1, '+46739000001', false);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertArrayHasKey('operation', $result['payment']);
        $this->assertArrayHasKey('intent', $result['payment']);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('Swish', $result['payment']['instrument']);
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));
        $this->assertIsString($result->getOperationByRel('create-sale'));
        $this->assertIsString($result->getOperationByRel('redirect-sale'));

        return $result;
    }

    /**
     * @depends SwishTest::testInitiateSwishPayment
     * @param Response $response
     */
    public function testInitiateSwishPaymentDirect(Response $response)
    {
        $result = $this->core->initiateSwishPaymentDirect(
            $response->getOperationByRel('create-sale'),
            '+46739000001'
        );

        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('sale', $result);
        $this->assertIsString($result['payment']);
        $this->assertIsArray($result['sale']);
        $this->assertArrayHasKey('id', $result['sale']);
        $this->assertArrayHasKey('transaction', $result['sale']);
        $this->assertArrayHasKey('date', $result['sale']);
        $this->assertArrayHasKey('swishFlowType', $result['sale']);
        $this->assertArrayHasKey('isPaymentRestrictedToSocialSecurityNumber', $result['sale']);
        $this->assertIsArray($result['sale']['transaction']);
    }
}
