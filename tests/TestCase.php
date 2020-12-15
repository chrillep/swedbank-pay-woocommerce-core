<?php

use SwedbankPay\Core\Core;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var Core
     */
    protected $core;

    protected function setUp(): void
    {
        if (!defined('ACCESS_TOKEN') ||
            ACCESS_TOKEN === '<access_token>') {
            $this->fail('ACCESS_TOKEN not configured in INI file or environment variable.');
        }

        if (!defined('PAYEE_ID') ||
            PAYEE_ID === '<payee_id>') {
            $this->fail('PAYEE_ID not configured in INI file or environment variable.');
        }

        $this->gateway = new Gateway();
        $this->adapter = new Adapter($this->gateway);
        $this->core = new Core($this->adapter);
    }

    protected function tearDown(): void
    {
        $this->gateway = null;
        $this->adapter = null;
        $this->core = null;
    }
}
