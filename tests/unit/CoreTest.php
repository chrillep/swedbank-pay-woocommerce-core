<?php

use SwedbankPay\Core\Core;

class CoreTest extends TestCase
{
    public function testCoreTest()
    {
        $this->gateway = new Gateway();
        $this->adapter = new Adapter($this->gateway);
        $this->core = new Core($this->adapter);

        $this->core->log('debug', 'Hello, world', [time()]);
        $this->assertEquals(true, file_exists(sys_get_temp_dir() . '/swedbankpay.log'));
    }

    public function testFormatErrorMessage()
    {
        $this->gateway = new Gateway();
        $this->adapter = new Adapter($this->gateway);
        $this->core = new Core($this->adapter);

        $responseBody = <<<DATA
{
    "type": "https://api.payex.com/psp/errordetail/inputerror",
    "title": "Error in input data",
    "status": 400,
    "instance": "https://api.payex.com/psp/payment/creditcard/00-d39554113af841da9ad82dc0f0292533-aacccdee47d4b554-01",
    "detail": "Input validation failed, error description in problems node!",
    "problems": [{
        "name": "Payment.Cardholder.Msisdn",
        "description": "The field Msisdn must match the regular expression '^[+][0-9]+$'"
    }, {
        "name": "Payment.Cardholder.HomePhoneNumber",
        "description": "The field HomePhoneNumber must match the regular expression '^[+][0-9]+$'"
    }, {
        "name": "Payment.Cardholder.WorkPhoneNumber",
        "description": "The field WorkPhoneNumber must match the regular expression '^[+][0-9]+$'"
    }, {
        "name": "Payment.Cardholder.BillingAddress.Msisdn",
        "description": "The field Msisdn must match the regular expression '^[+][0-9]+$'"
    }, {
        "name": "Payment.Cardholder.ShippingAddress.Msisdn",
        "description": "The field Msisdn must match the regular expression '^[+][0-9]+$'"
    }]
}
DATA;

        $result = $this->core->formatErrorMessage($responseBody);
        $this->assertEquals(
            'Your phone number format is wrong. Please input with country code, for example like this +46707777777',
            $result
        );
    }

    public function testSavePaymentTokens()
    {
        $this->markTestSkipped();

        $this->adapterMock->expects($this->once())
            ->method('savePaymentToken');

        $this->coreMock->savePaymentTokens(1);
    }

    public function testSavePaymentOrderTokens()
    {
        $response = <<<REPONSE
{
    "paid": {
        "id": "/psp/paymentorders/1893556a-aac8-498b-d008-08db05db225f/paid",
        "instrument": "CreditCard",
        "number": 40120452988,
        "payeeReference": "33996xxesyd",
        "orderReference": "33996",
        "transactionType": "Verification",
        "amount": 0,
        "submittedAmount": 0,
        "feeAmount": 0,
        "discountAmount": 0,
        "tokens": [{
            "type": "recurrence",
            "token": "12ad369c-3904-4ace-8047-ed9281c24b70",
            "name": "492500******0004",
            "expiryDate": "04/2023"
        }, {
            "type": "payment",
            "token": "d5500f32-7cab-4cd0-a978-1eba213679e2",
            "name": "492500******0004",
            "expiryDate": "04/2023"
        }, {
            "type": "unscheduled",
            "token": "32f0748b-43c1-44f8-9c15-94ae1f88ea18",
            "name": "492500******0004",
            "expiryDate": "04/2023"
        }],
        "details": {
            "cardBrand": "Visa",
            "cardType": "Credit",
            "maskedPan": "492500******0004",
            "expiryDate": "04/2023"
        }
    }
}
REPONSE;

        $this->clientMock->expects($this->at(1))
            ->method('getResponseBody')
            ->willReturn($this->paymentOrderResponse);

        $this->clientMock->expects($this->at(3))
            ->method('getResponseBody')
            ->willReturn($response);

        $this->adapterMock->expects($this->once())
            ->method('savePaymentToken');

        $this->coreMock->savePaymentOrderTokens(1);
    }
}
