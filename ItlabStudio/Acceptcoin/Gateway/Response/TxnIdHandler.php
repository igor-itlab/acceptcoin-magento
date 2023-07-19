<?php

namespace ItlabStudio\Acceptcoin\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * close the transaction
 */
class TxnIdHandler implements HandlerInterface
{
    const TXN_ID = 'TXN_ID';

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {

        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        /** @var $payment Payment */
        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setIsTransactionClosed(false);
    }
}
