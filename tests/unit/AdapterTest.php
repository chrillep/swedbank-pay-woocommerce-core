<?php

use SwedbankPay\Api\Service\Creditcard\Resource\Request\PaymentPurchaseObject;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Request\TransactionObject;

class AdapterTest extends TestCase
{
    public function testAdapter()
    {
        $paymentObject = new PaymentPurchaseObject();
        $result = $this->adapter->processPaymentObject($paymentObject, 123);
        $this->assertInstanceOf(PaymentPurchaseObject::class, $result);

        $transaction = new TransactionObject();
        $result = $this->adapter->processTransactionObject($transaction, 123);
        $this->assertInstanceOf(TransactionObject::class, $result);
    }
}
