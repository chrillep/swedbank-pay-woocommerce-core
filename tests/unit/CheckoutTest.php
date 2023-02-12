<?php

use SwedbankPay\Core\Api\Response;

class CheckoutTest extends TestCase
{
    public function testInitiatePaymentOrderPurchase()
    {
        // Test with mock
        $response = <<<END
{
	"paymentOrder": {
		"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0",
		"created": "2023-02-01T17:31:53.7200918Z",
		"updated": "2023-02-01T17:31:53.8703409Z",
		"operation": "Purchase",
		"state": "Ready",
		"currency": "SEK",
		"amount": 61600,
		"vatAmount": 12320,
		"orderItems": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/orderitems"
		},
		"description": "Order #10926",
		"initiatingSystemUserAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
		"userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
		"language": "sv-SE",
		"availableInstruments": ["CreditCard", "Invoice-PayExFinancingSe", "Swish", "CreditAccount-CreditAccountSe"],
		"integration": "",
		"urls": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/urls"
		},
		"payeeInfo": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/payeeInfo"
		},
		"metadata": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/metadata"
		},
		"payer": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/payers"
		},
		"payments": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/payments"
		},
		"currentPayment": {
			"id": "/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0/currentpayment"
		},
		"items": [{
			"creditCard": {
				"cardBrands": ["MasterCard", "Visa"]
			}
		}]
	},
	"operations": [{
		"method": "PATCH",
		"href": "https://api.externalintegration.payex.com/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0",
		"rel": "update-paymentorder-updateorder",
		"contentType": "application/json"
	}, {
		"method": "PATCH",
		"href": "https://api.externalintegration.payex.com/psp/paymentorders/b77594ee-9b9e-47fa-d13d-08db0362a6d0",
		"rel": "update-paymentorder-abort",
		"contentType": "application/json"
	}, {
		"method": "GET",
		"href": "https://ecom.externalintegration.payex.com/paymentmenu/13f388fd92efa8d726c6be2be2cecbf3b10b8fbe5be363e9726d9b9b5fa43f22",
		"rel": "redirect-paymentorder",
		"contentType": "text/html"
	}, {
		"method": "GET",
		"href": "https://ecom.externalintegration.payex.com/paymentmenu/core/client/paymentmenu/13f388fd92efa8d726c6be2be2cecbf3b10b8fbe5be363e9726d9b9b5fa43f22?culture=sv-SE",
		"rel": "view-paymentorder",
		"contentType": "application/javascript"
	}]
}
END;

        $this->clientMock->expects($this->once())
            ->method('getResponseBody')
            ->willReturn($response);

        $this->clientMock->expects($this->any())
            ->method('getResponseCode')
            ->willReturn(200);

        $result = $this->coreMock->initiatePaymentOrderPurchase(1, null, false);
        $this->assertInstanceOf(Response::class, $result);

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

    public function testInitiatePaymentOrderVerify()
    {
        $response = <<<RESPONSE
{
    "paymentOrder": {
        "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f",
        "created": "2023-02-06T05:41:00.7965012Z",
        "updated": "2023-02-06T05:41:00.8410496Z",
        "operation": "Verify",
        "state": "Ready",
        "currency": "EUR",
        "amount": 0,
        "vatAmount": 0,
        "description": "Verification of Credit Card",
        "initiatingSystemUserAgent": "Swedbank Pay PHP SDK/5.4.1 PHP/7.3.33 Mac OS X swedbankpay-woocommerce-checkout/6.4.0Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
        "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36",
        "language": "en-US",
        "availableInstruments": ["CreditCard"],
        "integration": "",
        "urls": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/urls"
        },
        "payeeInfo": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/payeeInfo"
        },
        "metadata": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/metadata"
        },
        "payer": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/payers"
        },
        "payments": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/payments"
        },
        "currentPayment": {
            "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/currentpayment"
        },
        "items": [{
            "creditCard": {
                "cardBrands": ["MasterCard", "Visa", "Amex", "Maestro"]
            }
        }]
    },
    "operations": [{
        "method": "PATCH",
        "href": "https://api.externalintegration.payex.com/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f",
        "rel": "update-paymentorder-updateorder",
        "contentType": "application/json"
    }, {
        "method": "PATCH",
        "href": "https://api.externalintegration.payex.com/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f",
        "rel": "update-paymentorder-abort",
        "contentType": "application/json"
    }, {
        "method": "GET",
        "href": "https://ecom.externalintegration.payex.com/paymentmenu/e9ade2b21f467c46d070dd4ff371912c222719d37450125519f51dc50a7272f9",
        "rel": "redirect-paymentorder",
        "contentType": "text/html"
    }, {
        "method": "GET",
        "href": "https://ecom.externalintegration.payex.com/paymentmenu/core/client/paymentmenu/e9ade2b21f467c46d070dd4ff371912c222719d37450125519f51dc50a7272f9?culture=en-US",
        "rel": "view-paymentorder",
        "contentType": "application/javascript"
    }]
}
RESPONSE;

        $this->clientMock->expects($this->once())
                         ->method('getResponseBody')
                         ->willReturn($response);

        $this->clientMock->expects($this->any())
                         ->method('getResponseCode')
                         ->willReturn(201);

        $result = $this->coreMock->initiatePaymentOrderVerify(1);

        $this->assertInstanceOf(Response::class, $result);
    }

}
