<?php

namespace SoftileLimited\Acceptcoin\Model\Api;

use Exception;
use SoftileLimited\Acceptcoin\Api\AcceptcoinApi;
use SoftileLimited\Acceptcoin\Api\Web\CallbackManagementInterface;
use SoftileLimited\Acceptcoin\Services\ACUtils;
use SoftileLimited\Acceptcoin\Services\MailHelper;
use SoftileLimited\Acceptcoin\Services\Signature;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Store\Model\ScopeInterface;

class CallbackManagement implements CallbackManagementInterface
{
    private const RESPONSE_STATUSES = [
        "PROCESSED"      => Order::STATE_PROCESSING,
        "FAIL"           => Order::STATE_CANCELED,
        "PENDING"        => Order::STATE_PENDING_PAYMENT,
        "FROZEN_DUE_AML" => Order::STATE_CANCELED
    ];

    private const PENDING_STATUSES = [Order::STATE_PENDING_PAYMENT, AcceptcoinApi::STATUS_PENDING];

    /**
     * @var Transaction
     */
    private Transaction $transaction;

    /**
     * @var InvoiceSender
     */
    private InvoiceSender $invoiceSender;

    /**
     * @var InvoiceRepositoryInterface
     */
    private InvoiceRepositoryInterface $invoiceRepository;

    /**
     * @var InvoiceService
     */
    private InvoiceService $invoiceService;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $repository;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $config;

    /**
     * @var OrderInterface
     */
    private OrderInterface $orderInterface;

    /**
     * @var MailHelper
     */
    private MailHelper $mailHelper;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param OrderInterface $orderInterface
     * @param OrderRepositoryInterface $repository
     * @param ScopeConfigInterface $config
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param InvoiceSender $invoiceSender
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param MailHelper $mailHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        OrderInterface             $orderInterface,
        OrderRepositoryInterface   $repository,
        ScopeConfigInterface       $config,
        InvoiceService             $invoiceService,
        Transaction                $transaction,
        InvoiceSender              $invoiceSender,
        InvoiceRepositoryInterface $invoiceRepository,
        MailHelper                 $mailHelper,
        ScopeConfigInterface       $scopeConfig
    )
    {
        $this->orderInterface = $orderInterface;
        $this->config = $config;
        $this->repository = $repository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceRepository = $invoiceRepository;
        $this->mailHelper = $mailHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function postCallback(): bool
    {
        $body = file_get_contents("php://input");
        $response = json_decode($body, true);

        if (!isset($response['data'])) {
            throw new Exception("Missing data", 400);
        }

        if (!is_array($response['data'])) {
            $response['data'] = json_decode($response['data'], true);
        }

        if (!isset($response['data']['referenceId'])) {
            throw new Exception("Missing data", 400);
        }

        if (!Signature::check(
            json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $response['signature'],
            $this->config->getValue('payment/acceptcoin/ac_secret_key', ScopeInterface::SCOPE_STORE)
        )) {
            throw new Exception("Invalid signature", 400);
        }

        $referenceArray = explode('-', $response['data']['referenceId']);

        if (!isset($referenceArray[2])) {
            throw new Exception("Invalid data", 400);
        }

        $orderId = $referenceArray[2];

        /** @var Order $order */
        $order = $this->orderInterface->loadByIncrementId($orderId);

        if (!$order || !in_array($order->getStatus(), self::PENDING_STATUSES)) {
            throw new Exception("Can't process the order", 400);
        }

        $responseStatus = self::RESPONSE_STATUSES[$response['data']['status']['value']];
        $order->setState($responseStatus)->setStatus($responseStatus);

        if ($order->getStatus() === Order::STATE_PROCESSING) {
            $processedAmount = ACUtils::convertAmount(
                ACUtils::getProcessedAmount($response['data']),
                ACUtils::STABLE_CURRENCY_CODE,
                $order->getBaseCurrencyCode()
            );

            $order->setTotalPaid($processedAmount);
            $order->setBaseTotalPaid($processedAmount);

            $this->createInvoice($order);
        }

        if ($response['data']['status']['value'] === "FROZEN_DUE_AML") {
            $emailVars = [
                'subject'       => "Dirty coins were identified through AML checks",
                "name"          => $order->getCustomerFirstname(),
                "lastname"      => $order->getCustomerLastname(),
                "referenceId"   => $response['data']['referenceId'],
                "transactionId" => $response['data']['id'],
                "date"          => date("Y-m-d H:i:s", $response['data']['createdAt']),
                "amount"        => $response['data']['amount'],
                "currency"      => $response['data']['projectPaymentMethods']['paymentMethod']['currency']['asset']
            ];

            $this->mailHelper->sendMessage(
                $order->getStoreId(),
                $order['customer_email'],
                'aml_frozen_template',
                $emailVars
            );
        }

        $this->repository->save($order);

        return true;
    }

    /**
     * @param $order
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    private function createInvoice($order): void
    {
        if (!$order->canInvoice()) {
            return;
        }

        $invoice = $this->invoiceService->prepareInvoice($order);

        $invoice->setGrandTotal($order->getTotalPaid());
        $invoice->setBaseGrandTotal($order->getTotalPaid());
        $invoice->register();
        $invoice->pay();
        $this->invoiceRepository->save($invoice);

        $transactionSave = $this->transaction
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transactionSave->save();
        $this->invoiceSender->send($invoice);
    }
}
