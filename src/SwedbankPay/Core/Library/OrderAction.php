<?php

namespace SwedbankPay\Core\Library;

use InvalidArgumentException;
use SwedbankPay\Core\Api\Response;
use SwedbankPay\Core\Api\Transaction;
use SwedbankPay\Core\Api\TransactionInterface;
use SwedbankPay\Core\Exception;
use SwedbankPay\Core\Log\LogLevel;
use SwedbankPay\Core\Order;
use SwedbankPay\Core\OrderInterface;
use SwedbankPay\Core\PaymentAdapterInterface;

use SwedbankPay\Api\Client\Exception as ClientException;
use SwedbankPay\Api\Service\Data\ResponseInterface as ResponseServiceInterface;
use SwedbankPay\Api\Service\Payment\Transaction\Resource\Request\TransactionObject;
use SwedbankPay\Api\Service\Creditcard\Transaction\Request\CreateCapture;
use SwedbankPay\Api\Service\Creditcard\Transaction\Request\CreateReversal;
use SwedbankPay\Api\Service\Creditcard\Transaction\Request\CreateCancellation;
use SwedbankPay\Api\Service\Creditcard\Transaction\Resource\Request\TransactionCapture;
use SwedbankPay\Api\Service\Creditcard\Transaction\Resource\Request\TransactionReversal;
use SwedbankPay\Api\Service\Creditcard\Transaction\Resource\Request\TransactionCancellation;

trait OrderAction
{
    /**
     * Can Capture.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canCapture($orderId, $amount = null)
    {
        // Check the payment state online
        $order = $this->getOrder($orderId);

        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            // Fetch payment info
            try {
                $result = $this->fetchPaymentInfo($order->getPaymentOrderId());
            } catch (\Exception $e) {
                // Request failed
                return false;
            }

            return isset($result['paymentOrder']['remainingCaptureAmount'])
                   && (float)$result['paymentOrder']['remainingCaptureAmount'] > 0.1;
        }

        // Fetch payment info
        try {
            $result = $this->fetchPaymentInfo($order->getPaymentId());
        } catch (\Exception $e) {
            // Request failed
            return false;
        }

        return isset($result['payment']['remainingCaptureAmount'])
            && (float)$result['payment']['remainingCaptureAmount'] > 0.1;
    }

    /**
     * Can Cancel.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function canCancel($orderId, $amount = null)
    {
        // Check the payment state online
        $order = $this->getOrder($orderId);

        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            // Fetch payment info
            try {
                $result = $this->fetchPaymentInfo($order->getPaymentOrderId());
            } catch (\Exception $e) {
                // Request failed
                return false;
            }

            return isset($result['paymentOrder']['remainingCancellationAmount'])
                   && (float)$result['paymentOrder']['remainingCancellationAmount'] > 0.1;
        }

        // Fetch payment info
        try {
            $result = $this->fetchPaymentInfo($order->getPaymentId());
        } catch (\Exception $e) {
            // Request failed
            return false;
        }

        return isset($result['payment']['remainingCancellationAmount'])
            && (float)$result['payment']['remainingCancellationAmount'] > 0.1;
    }

    /**
     * Can Refund.
     *
     * @param mixed $orderId
     * @param float|int|null $amount
     *
     * @return bool
     */
    public function canRefund($orderId, $amount = null)
    {
        $order = $this->getOrder($orderId);

        if (!$amount) {
            $amount = $order->getAmount();
        }

        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            // Fetch payment info
            try {
                $result = $this->fetchPaymentInfo($order->getPaymentOrderId());
            } catch (\Exception $e) {
                // Request failed
                return false;
            }

            return isset($result['paymentOrder']['remainingReversalAmount'])
                   && (float)$result['paymentOrder']['remainingReversalAmount'] > 0.1;
        }

        // Should has payment id
        $paymentId = $order->getPaymentId();
        if (!$paymentId) {
            return false;
        }

        // Should be captured
        // @todo Check payment state

        // Check refund amount
        $transactions = $this->fetchTransactionsList($order->getPaymentId());

        $refunded = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->getType() === $transaction::TYPE_REVERSAL) {
                $refunded += ($transaction->getAmount() / 100);
            }
        }

        $possibleToRefund = $order->getAmount() - $refunded;
        if ($amount > $possibleToRefund) {
            return false;
        }

        return true;
    }

    /**
     * Capture.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function capture($orderId, $amount = null, $vatAmount = 0)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        if (!$amount) {
            $amount = $order->getAmount();
            $vatAmount = $order->getVatAmount();
        }

        if (!$this->canCapture($orderId, $amount)) {
            throw new Exception('Capturing is not available.');
        }

        // Use the checkout method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            return $this->captureCheckout($orderId);
        }

        // Use the invoice method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_INVOICE) {
            // @todo Should we use different credentials?
            return $this->captureInvoice($orderId);
        }

        $paymentId = $order->getPaymentId();
        if (empty($paymentId)) {
            throw new Exception('Unable to get payment ID');
        }

        $transactionData = new TransactionCapture();
        $transactionData
            ->setAmount((int)bcmul(100, $amount))
            ->setVatAmount((int)bcmul(100, $vatAmount))
            ->setDescription(sprintf('Capture for Order #%s', $order->getOrderId()))
            ->setPayeeReference($this->generatePayeeReference($orderId));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        // Process transaction object
        $transaction = $this->adapter->processTransactionObject($transaction, $orderId);

        $requestService = new CreateCapture($transaction);
        $requestService->setClient($this->client);
        $requestService->setPaymentId($paymentId);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $result = $responseService->getResponseData();

            // Save transaction
            /** @var Transaction $transaction */
            $transaction = $result['capture']['transaction'];
            if (is_array($transaction)) {
                $transaction = new Transaction($transaction);
            }

            $this->saveTransaction($orderId, $transaction);
            $this->processTransaction($orderId, $transaction);

            return new Response($result);
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Cancel.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function cancel($orderId, $amount = null, $vatAmount = 0)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        if (!$amount) {
            $amount = $order->getAmount();
            $vatAmount = $order->getVatAmount();
        }

        if (!$this->canCancel($orderId, $amount)) {
            throw new Exception('Cancellation is not available.');
        }

        // Use the checkout method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            return $this->cancelCheckout($orderId, $amount, $vatAmount);
        }

        // Use the invoice method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_INVOICE) {
            // @todo Should we use different credentials?
            return $this->cancelInvoice($orderId, $amount, $vatAmount);
        }

        $paymentId = $order->getPaymentId();
        if (empty($paymentId)) {
            throw new Exception('Unable to get payment ID');
        }

        $transactionData = new TransactionCancellation();
        $transactionData
            ->setDescription(sprintf('Cancellation for Order #%s', $order->getOrderId()))
            ->setPayeeReference($this->generatePayeeReference($orderId));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        // Process transaction object
        $transaction = $this->adapter->processTransactionObject($transaction, $orderId);

        $requestService = new CreateCancellation($transaction);
        $requestService->setClient($this->client);
        $requestService->setPaymentId($paymentId);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $result = $responseService->getResponseData();

            // Save transaction
            /** @var Transaction $transaction */
            $transaction = $result['cancellation']['transaction'];
            if (is_array($transaction)) {
                $transaction = new Transaction($transaction);
            }

            $this->saveTransaction($orderId, $transaction);
            $this->processTransaction($orderId, $transaction);

            return new Response($result);
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Refund.
     *
     * @param mixed $orderId
     * @param mixed $amount
     * @param mixed $vatAmount
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function refund($orderId, $amount = null, $vatAmount = 0)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        if (!$amount) {
            $amount = $order->getAmount();
            $vatAmount = $order->getVatAmount();
        }

        if (!$this->canRefund($orderId, $amount)) {
            throw new Exception('Refund action is not available.');
        }

        // Use the checkout method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            return $this->refundCheckout($orderId, $order->getItems());
        }

        // Use the invoice method if possible
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_INVOICE) {
            // @todo Should we use different credentials?
            return $this->refundInvoice($orderId, $amount, $vatAmount);
        }

        $paymentId = $order->getPaymentId();
        if (empty($paymentId)) {
            throw new Exception('Unable to get payment ID');
        }

        $transactionData = new TransactionReversal();
        $transactionData
            ->setAmount((int)bcmul(100, $amount))
            ->setVatAmount((int)bcmul(100, $vatAmount))
            ->setDescription(sprintf('Refund for Order #%s.', $order->getOrderId()))
            ->setPayeeReference($this->generatePayeeReference($orderId));

        $transaction = new TransactionObject();
        $transaction->setTransaction($transactionData);

        // Process transaction object
        $transaction = $this->adapter->processTransactionObject($transaction, $orderId);

        $requestService = new CreateReversal($transaction);
        $requestService->setClient($this->client);
        $requestService->setPaymentId($paymentId);

        try {
            /** @var ResponseServiceInterface $responseService */
            $responseService = $requestService->send();
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $result = $responseService->getResponseData();

            // Save transaction
            /** @var Transaction $transaction */
            $transaction = $result['reversal']['transaction'];
            if (is_array($transaction)) {
                $transaction = new Transaction($transaction);
            }

            $this->saveTransaction($orderId, $transaction);
            $this->processTransaction($orderId, $transaction);

            return new Response($result);
        } catch (ClientException $e) {
            $this->log(
                LogLevel::DEBUG,
                $requestService->getClient()->getDebugInfo()
            );

            $this->log(
                LogLevel::DEBUG,
                sprintf('%s: API Exception: %s', __METHOD__, $e->getMessage())
            );

            throw new Exception($e->getMessage());
        }
    }

    /**
     * Abort Payment.
     *
     * @param mixed $orderId
     *
     * @return Response
     * @throws Exception
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function abort($orderId)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        $paymentId = $order->getPaymentId();
        if (empty($paymentId)) {
            throw new Exception('Unable to get payment ID');
        }

        // @todo Check if order has been paid
        $href = $this->fetchPaymentInfo($paymentId)->getOperationByRel('update-payment-abort');
        if (empty($href)) {
            throw new Exception('Abort is unavailable');
        }

        $params = [
            'payment' => [
                'operation' => 'Abort',
                'abortReason' => 'CancelledByConsumer'
            ]
        ];
        $result = $this->request('PATCH', $href, $params);

        if ($result['payment']['state'] === 'Aborted') {
            $this->updateOrderStatus(
                $orderId,
                OrderInterface::STATUS_CANCELLED,
                'Payment aborted'
            );
        } else {
            throw new Exception('Aborting is failed.');
        }

        return $result;
    }

    /**
     * Check if order status can be updated.
     *
     * @param mixed $orderId
     * @param string $status
     * @param string|null $transactionNumber
     * @return bool
     */
    public function canUpdateOrderStatus($orderId, $status, $transactionNumber = null)
    {
        return $this->adapter->canUpdateOrderStatus($orderId, $status, $transactionNumber);
    }

    /**
     * Get Order Status.
     *
     * @param mixed $orderId
     *
     * @return string
     * @throws Exception
     */
    public function getOrderStatus($orderId)
    {
        return $this->adapter->getOrderStatus($orderId);
    }

    /**
     * Set Payment Id to Order.
     *
     * @param mixed $orderId
     * @param string $paymentId
     *
     * @return void
     */
    public function setPaymentId($orderId, $paymentId)
    {
        $this->adapter->setPaymentId($orderId, $paymentId);
    }

    /**
     * Set Payment Order Id to Order.
     *
     * @param mixed $orderId
     * @param string $paymentOrderId
     *
     * @return void
     */
    public function setPaymentOrderId($orderId, $paymentOrderId)
    {
        $this->adapter->setPaymentOrderId($orderId, $paymentOrderId);
    }

    /**
     * Update Order Status.
     *
     * @param mixed $orderId
     * @param string $status
     * @param string|null $message
     * @param string|null $transactionNumber
     */
    public function updateOrderStatus($orderId, $status, $message = null, $transactionNumber = null)
    {
        if ($this->canUpdateOrderStatus($orderId, $status)) {
            $this->adapter->updateOrderStatus($orderId, $status, $message, $transactionNumber);
        }
    }

    /**
     * Add Order Note.
     *
     * @param mixed $orderId
     * @param string $message
     */
    public function addOrderNote($orderId, $message)
    {
        $this->adapter->addOrderNote($orderId, $message);
    }

    /**
     * Get Payment Method.
     *
     * @param mixed $orderId
     *
     * @return string|null Returns method or null if not exists
     */
    public function getPaymentMethod($orderId)
    {
        return $this->adapter->getPaymentMethod($orderId);
    }

    /**
     * Fetch Transactions related to specific order, process transactions and
     * update order status.
     *
     * @param mixed $orderId
     * @param string|null $transactionNumber
     * @throws Exception
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function fetchTransactionsAndUpdateOrder($orderId, $transactionNumber = null)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        // Get Payment ID
        if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            $paymentId = $this->getPaymentIdByPaymentOrder($order->getPaymentOrderId());
        } else {
            $paymentId = $order->getPaymentId();
        }

        if (empty($paymentId)) {
            throw new Exception('Unable to get payment ID');
        }

        // Fetch transactions list
        $transactions = $this->fetchTransactionsList($paymentId);
        $this->saveTransactions($orderId, $transactions);

        // Extract transaction from list
        if ($transactionNumber) {
            $transaction = $this->findTransaction('number', $transactionNumber);
            if (! $transaction) {
                throw new Exception(sprintf('Failed to fetch transaction number #%s', $transactionNumber));
            }

            $transactions = [ $transaction ];
        }

        // Process transactions
        foreach ($transactions as $transaction) {
            try {
                $this->processTransaction($orderId, $transaction);
            } catch (Exception $exception) {
                $this->log(
                    LogLevel::ERROR,
                    sprintf('%s: API Exception: %s', __METHOD__, $exception->getMessage())
                );

                continue;
            }
        }
    }

    /**
     * Analyze the transaction and update the related order.
     *
     * @param $orderId
     * @param Transaction|array $transaction
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function processTransaction($orderId, $transaction)
    {
        /** @var Order $order */
        $order = $this->getOrder($orderId);

        if (is_array($transaction)) {
            $transaction = new Transaction($transaction);
        } elseif (!$transaction instanceof Transaction) {
            throw new InvalidArgumentException('Invalid a transaction parameter');
        }

        // Get Payment ID
        $paymentId = $order->getPaymentId();
        if (empty($paymentId) && $order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
            $paymentId = $this->getPaymentIdByPaymentOrder($order->getPaymentOrderId());
            $order->setPaymentId($paymentId);
        }

        // Apply action
        switch ($transaction->getType()) {
            case TransactionInterface::TYPE_VERIFICATION:
                if ($transaction->isFailed()) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf(
                            'Verification has been failed. Transaction: %s. Reason: %s.',
                            $transaction->getNumber(),
                            $transaction->getFailedDetails()
                        )
                    );

                    break;
                }

                if ($transaction->isPending()) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf(
                            'Verification transaction is pending. Transaction: %s',
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                // Save Payment Token
                $verifications = $this->fetchVerificationList($order->getPaymentId());
                foreach ($verifications as $verification) {
                    // Skip verification which failed transaction state
                    if ($verification->getTransaction()->isFailed()) {
                        continue;
                    }

                    if ($verification->getPaymentToken() || $verification->getRecurrenceToken()) {
                        // Add payment token
                        $this->adapter->savePaymentToken(
                            $order->getCustomerId(),
                            $verification->getPaymentToken(),
                            $verification->getRecurrenceToken(),
                            $verification->getCardBrand(),
                            $verification->getMaskedPan(),
                            $verification->getExpireDate(),
                            $order->getOrderId()
                        );

                        // Use the first item only
                        break;
                    }
                }

                break;
            case TransactionInterface::TYPE_AUTHORIZATION:
                if ($transaction->isFailed()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_FAILED,
                        sprintf(
                            'Authorization has been failed. Transaction: %s. Reason: %s.',
                            $transaction->getNumber(),
                            $transaction->getFailedDetails()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isPending()) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf(
                            'Authorization is pending. Amount: %s. Transaction: %s',
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                if ($transaction->isInitialized() || $transaction->isAwaitingActivity()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_AUTHORIZED,
                        sprintf(
                            'Authorization is %s. Amount: %s. Transaction: %s',
                            $transaction->getState(),
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                // Don't change the order status if it was captured before
                if ($order->getStatus() === OrderInterface::STATUS_CAPTURED) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf('Payment has been authorized. Transaction: %s', $transaction->getNumber())
                    );
                } else {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_AUTHORIZED,
                        sprintf('Payment has been authorized. Transaction: %s', $transaction->getNumber()),
                        $transaction->getNumber()
                    );
                }

                // Save Payment Token
                if ($order->needsSaveToken()) {
                    $authorizations = $this->fetchAuthorizationList($order->getPaymentId());
                    foreach ($authorizations as $authorization) {
                        if ($authorization->getPaymentToken() || $authorization->getRecurrenceToken()) {
                            // Add payment token
                            $this->adapter->savePaymentToken(
                                $order->getCustomerId(),
                                $authorization->getPaymentToken(),
                                $authorization->getRecurrenceToken(),
                                $authorization->getCardBrand(),
                                $authorization->getMaskedPan(),
                                $authorization->getExpireDate(),
                                $order->getOrderId()
                            );

                            // Use the first item only
                            break;
                        }
                    }
                }

                break;
            case TransactionInterface::TYPE_CAPTURE:
            case TransactionInterface::TYPE_SALE:
                if ($transaction->isFailed()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_FAILED,
                        sprintf(
                            'Capture has been failed. Transaction: %s. Reason: %s.',
                            $transaction->getNumber(),
                            $transaction->getFailedDetails()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isInitialized() || $transaction->isAwaitingActivity()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_AUTHORIZED,
                        sprintf(
                            'Capture is %s. Amount: %s. Transaction: %s',
                            $transaction->getState(),
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                if ($transaction->isPending()) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf(
                            'Capture is pending. Amount: %s. Transaction: %s',
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                if ($transaction->isCompleted()) {
                    // Fetch payment info
                    if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
                        $paymentInfo = $this->fetchPaymentInfo($order->getPaymentOrderId());
                        $paymentBody = $paymentInfo['paymentOrder'];
                    } else {
                        $paymentInfo = $this->fetchPaymentInfo($order->getPaymentId());
                        $paymentBody = $paymentInfo['payment'];
                    }

                    // Check if the payment was captured fully
                    // `remainingCaptureAmount` is missing if the payment was captured fully
                    $isFullCapture = false;
                    if (!isset($paymentBody['remainingCaptureAmount'])) {
                        $isFullCapture = true;
                    }

                    // Update order status
                    if ($isFullCapture) {
                        $this->updateOrderStatus(
                            $orderId,
                            OrderInterface::STATUS_CAPTURED,
                            sprintf(
                                'Payment has been captured. Transaction: %s. Amount: %s',
                                $transaction->getNumber(),
                                $transaction->getAmount() / 100
                            ),
                            $transaction->getNumber()
                        );
                    } else {
                        $this->addOrderNote(
                            $orderId,
                            sprintf(
                                'Payment has been partially captured: Transaction: %s. Amount: %s',
                                $transaction->getNumber(),
                                $transaction->getAmount() / 100
                            )
                        );
                    }
                }

                break;
            case TransactionInterface::TYPE_CANCELLATION:
                if ($transaction->isFailed()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_FAILED,
                        sprintf(
                            'Cancellation has been failed. Transaction: %s. Reason: %s.',
                            $transaction->getNumber(),
                            $transaction->getFailedDetails()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isInitialized() || $transaction->isAwaitingActivity()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_CANCELLED,
                        sprintf(
                            'Cancellation is %s. Transaction ID: %s',
                            $transaction->getState(),
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                if ($transaction->isPending()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_CANCELLED,
                        sprintf(
                            'Cancellation is pending. Transaction: %s',
                            $transaction->getNumber()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isCompleted()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_CANCELLED,
                        sprintf('Payment has been cancelled. Transaction: %s', $transaction->getNumber()),
                        $transaction->getNumber()
                    );
                }

                break;
            case TransactionInterface::TYPE_REVERSAL:
                if ($transaction->isFailed()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_FAILED,
                        sprintf(
                            'Reversal has been failed. Transaction: %s. Reason: %s.',
                            $transaction->getNumber(),
                            $transaction->getFailedDetails()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isInitialized() || $transaction->isAwaitingActivity()) {
                    $this->addOrderNote(
                        $orderId,
                        sprintf(
                            'Reversal is %s. Amount: %s. Transaction ID: %s',
                            $transaction->getState(),
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        )
                    );

                    break;
                }

                if ($transaction->isPending()) {
                    $this->updateOrderStatus(
                        $orderId,
                        OrderInterface::STATUS_REFUNDED,
                        sprintf(
                            'Reversal is pending. Amount: %s, Transaction: %s',
                            $transaction->getAmount() / 100,
                            $transaction->getNumber()
                        ),
                        $transaction->getNumber()
                    );

                    break;
                }

                if ($transaction->isCompleted()) {
                    // Fetch payment info
                    if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
                        $paymentInfo = $this->fetchPaymentInfo($order->getPaymentOrderId());
                        $paymentBody = $paymentInfo['paymentOrder'];
                    } else {
                        $paymentInfo = $this->fetchPaymentInfo($order->getPaymentId());
                        $paymentBody = $paymentInfo['payment'];
                    }

                    // Create Credit Memo
                    $this->createCreditMemo(
                        $orderId,
                        $transaction->getAmount() / 100,
                        $transaction->getNumber(),
                        $transaction->getDescription()
                    );

                    // Check if the payment was refunded fully
                    // `remainingReversalAmount` is missing if the payment was refunded fully
                    $isFullRefund = false;
                    if (!isset($paymentBody['remainingReversalAmount'])) {
                        $isFullRefund = true;
                    }

                    // Update order status
                    if ($isFullRefund) {
                        $this->updateOrderStatus(
                            $orderId,
                            OrderInterface::STATUS_REFUNDED,
                            sprintf(
                                'Payment has been refunded. Transaction: %s. Amount: %s',
                                $transaction->getNumber(),
                                $transaction->getAmount() / 100
                            ),
                            $transaction->getNumber()
                        );
                    } else {
                        $this->addOrderNote(
                            $orderId,
                            sprintf(
                                'Payment has been partially refunded: Transaction: %s. Amount: %s',
                                $transaction->getNumber(),
                                $transaction->getAmount() / 100
                            )
                        );
                    }
                }

                break;
            default:
                throw new Exception(sprintf('Error: Unknown type %s', $transaction->getType()));
        }
    }

    /**
     * Generate Payee Reference for Order.
     *
     * @param mixed $orderId
     *
     * @return string
     */
    public function generatePayeeReference($orderId)
    {
        // Use the reference from the adapter if exists
        return $this->adapter->generatePayeeReference($orderId);
    }

    /**
     * Create Credit Memo.
     *
     * @param mixed $orderId
     * @param float $amount
     * @param mixed $transactionId
     * @param string $description
     */
    public function createCreditMemo($orderId, $amount, $transactionId, $description)
    {
        // Check if a credit memo was created before
        if ($this->isCreditMemoExist($transactionId)) {
            return;
        }

        try {
            $this->adapter->createCreditMemo($orderId, $amount, $transactionId, $description);
        } catch (Exception $e) {
            $this->addOrderNote(
                $orderId,
                sprintf(
                    'Unable to create credit memo. %s',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Check if Credit Memo exist.
     *
     * @param string $transactionId
     *
     * @return bool
     */
    public function isCreditMemoExist($transactionId)
    {
        return $this->adapter->isCreditMemoExist($transactionId);
    }

    /**
     * Update Transactions On Failure.
     *
     * @param mixed $orderId
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function updateTransactionsOnFailure($orderId)
    {
        /** @var OrderInterface $order */
        $order = $this->getOrder($orderId);

        if (OrderInterface::STATUS_FAILED === $order->getStatus()) {
            // Wait for "Completed" transaction state
            // Current payment can be changed
            $attempts = 0;
            while (true) {
                sleep(1);
                $attempts++;
                if ($attempts > 60) {
                    break;
                }

                // Get Payment ID
                if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
                    $paymentId = $this->getPaymentIdByPaymentOrder($order->getPaymentOrderId());
                } else {
                    $paymentId = $order->getPaymentId();
                }

                $transactions = $this->fetchTransactionsList($paymentId);
                foreach ($transactions as $transaction) {
                    /** @var Transaction $transaction */
                    if (in_array($transaction->getType(), [
                        TransactionInterface::TYPE_AUTHORIZATION,
                        TransactionInterface::TYPE_SALE
                    ])) {
                        switch ($transaction->getState()) {
                            case TransactionInterface::STATE_COMPLETED:
                                // Transaction has found: update the order state
                                if ($order->getPaymentMethod() === PaymentAdapterInterface::METHOD_CHECKOUT) {
                                    $this->setPaymentId($orderId, $paymentId);
                                }

                                $this->fetchTransactionsAndUpdateOrder($orderId, $transaction->getNumber());
                                break 3;
                            case TransactionInterface::STATE_FAILED:
                                // Log failed transaction
                                $this->log(
                                    LogLevel::WARNING,
                                    sprintf(
                                        'Failed transaction: (%s), (%s), (%s), (%s)',
                                        $orderId,
                                        $paymentId,
                                        $transaction->getId(),
                                        var_export($transaction->getData(), true)
                                    )
                                );

                                break;
                        }
                    }
                }
            }
        }
    }
}
