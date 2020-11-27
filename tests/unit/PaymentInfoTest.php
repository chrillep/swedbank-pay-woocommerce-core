<?php

class PaymentInfoTest extends TestCase
{
    public function testCheckApiCredentials()
    {
        $result = $this->core->checkApiCredentials();
        $this->assertTrue($result);

        return $result;
    }

}
