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
}
