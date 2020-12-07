<?php

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

class InvoiceTest extends TestCase
{
    public function testInitiateInvoicePayment()
    {
        // Test initialization
        $result = $this->core->initiateInvoicePayment(1);
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('operations', $result);
        $this->assertIsArray($result['payment']);
        $this->assertArrayHasKey('id', $result['payment']);
        $this->assertArrayHasKey('number', $result['payment']);
        $this->assertIsString($result->getOperationByRel('create-approved-legal-address'));
        $this->assertIsString($result->getOperationByRel('create-authorization'));
        $this->assertIsString($result->getOperationByRel('update-payment-abort'));
        $this->assertIsString($result->getOperationByRel('redirect-authorization'));

        return $result;
    }

    /**
     * @depends InvoiceTest::testInitiateInvoicePayment
     */
    public function testApprovedLegalAddress(Response $response)
    {
        $result = $this->core->getApprovedLegalAddress(
            $response->getOperationByRel('create-approved-legal-address'),
            '971020-2392',
            '17674'
        );
        $this->assertInstanceOf(Response::class, $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('approvedLegalAddress', $result);
        $this->assertArrayHasKey('id', $result['approvedLegalAddress']);
        $this->assertArrayHasKey('addressee', $result['approvedLegalAddress']);
        $this->assertArrayHasKey('streetAddress', $result['approvedLegalAddress']);
        $this->assertArrayHasKey('zipCode', $result['approvedLegalAddress']);
        $this->assertArrayHasKey('city', $result['approvedLegalAddress']);
        $this->assertArrayHasKey('countryCode', $result['approvedLegalAddress']);
    }

    /**
     * @depends InvoiceTest::testInitiateInvoicePayment
     */
    public function testTransactionFinancingConsumer(Response $response)
    {
        $this->expectException(Exception::class);
        $this->core->transactionFinancingConsumer(
            $response->getOperationByRel('create-authorization'),
            1,
            '971020-2392',
            'Leia Ahlström',
            '',
            'Hökvägen 5',
            '17674',
            'Järfälla',
            'SE'
        );

        //SwedbankPay\Core\Exception: Error in external dependency. Third party returned error
        //ThirdPartyError: Unspecified error from third party. Response code 60
    }

    /**
     * @depends InvoiceTest::testInitiateInvoicePayment
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
