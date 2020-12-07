<?php

use SwedbankPay\Core\Api\Response;

class TrustlyTest extends TestCase
{
    public function testInitiateTrustlyPayment()
    {
        $this->gateway->currency = 'SEK';
        $result = $this->core->initiateTrustlyPayment(1);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertArrayHasKey('operation', $result['payment']);
        $this->assertArrayHasKey('intent', $result['payment']);
        $this->assertArrayHasKey('state', $result['payment']);
        $this->assertEquals('Trustly', $result['payment']['instrument']);
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));
        $this->assertIsString($result->getOperationByRel('create-sale'));
        $this->assertIsString($result->getOperationByRel('redirect-sale'));

        return $result;
    }

    /**
     * @depends TrustlyTest::testInitiateTrustlyPayment
     * @param Response $response
     */
    public function testTrustlyPaymentPaymentPage(Response $response)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $response->getOperationByRel('redirect-sale'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $this->assertIsString($output);
        $this->assertEquals(200, $code);
    }
}
