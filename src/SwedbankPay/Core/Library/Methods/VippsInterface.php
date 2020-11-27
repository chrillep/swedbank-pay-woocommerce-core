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
	 * Check Vipps API Credentials.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkVippsApiCredentials();

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
