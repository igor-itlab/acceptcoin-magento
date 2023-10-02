<?php

namespace SoftileLimited\Acceptcoin\Observer;

use Exception;
use SoftileLimited\Acceptcoin\Api\AcceptcoinApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class PlaceOrderObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $repository;

    /**
     * @param OrderRepositoryInterface $repository
     */
    public function __construct(OrderRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getStatus() === Order::STATE_PROCESSING) {
            $order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(AcceptcoinApi::STATUS_PENDING);
            $this->repository->save($order);
        }
    }
}
