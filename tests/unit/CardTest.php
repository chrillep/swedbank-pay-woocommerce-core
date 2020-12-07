<?php

use SwedbankPay\Core\Api\Response;

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

}
