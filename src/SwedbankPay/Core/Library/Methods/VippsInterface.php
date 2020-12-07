<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Exception;

/**
 * Interface VippsInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface VippsInterface
{
	/**
	 * Initiate Vipps Payment.
	 *
	 * @param mixed $orderId
	 * @param string $phone
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function initiateVippsPayment($orderId, $phone);
}
