<?php

namespace SwedbankPay\Core\Library\Methods;

use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Exception;

/**
 * Interface SwishInterface
 * @package SwedbankPay\Core\Library\Methods
 */
interface SwishInterface
{
	/**
	 * Check Swish API Credentials.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkSwishApiCredentials();

	/**
	 * Initiate Swish Payment
	 *
	 * @param mixed $orderId
	 * @param string $phone
	 * @param bool $ecomOnlyEnabled
	 *
	 * @return Response
	 * @throws Exception
	 */
	public function initiateSwishPayment($orderId, $phone, $ecomOnlyEnabled = true);

	/**
	 * initiate Swish Payment Direct
	 *
	 * @param string $saleHref
	 * @param string $phone
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function initiateSwishPaymentDirect($saleHref, $phone);
}
