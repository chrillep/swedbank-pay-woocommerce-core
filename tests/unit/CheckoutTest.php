<?php

use SwedbankPay\Core\Api\Response;

class CheckoutTest extends TestCase
{
    public function testInitiatePaymentOrderPurchase()
    {
        // Test initialization
        $result = $this->core->initiatePaymentOrderPurchase(1, null);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment_order', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment_order']);
        $this->assertArrayHasKey('id', $result['payment_order']);
        $this->assertArrayHasKey('operation', $result['payment_order']);
        $this->assertArrayHasKey('state', $result['payment_order']);
        $this->assertArrayHasKey('items', $result['payment_order']);
        $this->assertIsString($result->getOperationByRel('update-paymentorder-updateorder'));
        $this->assertIsString($result->getOperationByRel('update-paymentorder-abort'));
        $this->assertIsString($result->getOperationByRel('redirect-paymentorder'));
        $this->assertIsString($result->getOperationByRel('view-paymentorder'));

        return $result;
    }

    /**
     * @depends CheckoutTest::testInitiatePaymentOrderPurchase
     * @param Response $response
     */
    public function testUpdatePaymentOrder(Response $response)
    {
        $result = $this->core->updatePaymentOrder(
            $response->getOperationByRel('update-paymentorder-updateorder'),
            1
        );

        $this->assertIsString($result->getOperationByRel('update-paymentorder-updateorder'));
        $this->assertIsString($result->getOperationByRel('update-paymentorder-abort'));
        $this->assertIsString($result->getOperationByRel('redirect-paymentorder'));
        $this->assertIsString($result->getOperationByRel('view-paymentorder'));
    }

    /**
     * @depends CheckoutTest::testInitiatePaymentOrderPurchase
     * @param Response $response
     */
    public function testGetPaymentIdByPaymentOrder(Response $response)
    {
        $paymentId = $this->core->getPaymentIdByPaymentOrder($response['payment_order']['id']);
        $this->assertEquals(false, $paymentId);
    }

    /**
     * @depends CheckoutTest::testInitiatePaymentOrderPurchase
     */
    public function testAbort(Response $response)
    {
        // Test abort
        $result = $this->core->request(
            'PATCH',
            $response->getOperationByRel('update-paymentorder-abort'),
            [
                'paymentorder' => [
                    'operation' => 'Abort',
                    'abortReason' => 'CancelledByConsumer'
                ]
            ]
        );

        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('state', $result['paymentOrder']);
        $this->assertEquals('Aborted', $result['paymentOrder']['state']);
        $this->assertEquals('CancelledByConsumer', $result['paymentOrder']['stateReason']);
    }

}
