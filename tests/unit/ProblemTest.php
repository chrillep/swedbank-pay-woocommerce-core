<?php

use SwedbankPay\Core\Api\Problem;
use SwedbankPay\Core\Api\ProblemInterface;

class ProblemTest extends TestCase
{
    public function testData()
    {
        $data = '{
    "type": "https://api.payex.com/psp/errordetail/creditcard/authenticationrequired",
    "title": "Operation failed",
    "status": 403,
    "detail": "Unable to complete Authorization transaction, look at problem node!",
    "problems": [{
        "name": "ExternalResponse",
        "description": "AUTHENTICATION_REQUIRED-Acquirer soft-decline, 3-D Secure authentication required, response-code: O5"
    }]
}';
        $data = json_decode($data, true);

        $problem = new Problem($data);
        $this->assertInstanceOf(ProblemInterface::class, $problem);
        $this->assertEquals(
            'https://api.payex.com/psp/errordetail/creditcard/authenticationrequired',
            $problem->getType()
        );
        $this->assertEquals(
            'Operation failed',
            $problem->getTitle()
        );
        $this->assertEquals(
            403,
            $problem->getStatus()
        );
        $this->assertEquals(
            'Unable to complete Authorization transaction, look at problem node!',
            $problem->getDetail()
        );
        $this->assertIsArray($problem->getProblems());
        $this->assertIsString($problem->toString());
    }
}
