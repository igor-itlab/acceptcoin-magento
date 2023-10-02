<?php

namespace SoftileLimited\Acceptcoin\Gateway\Request;

use Exception;
use SoftileLimited\Acceptcoin\Api\AcceptcoinApi;
use SoftileLimited\Acceptcoin\Services\MailHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use SoftileLimited\Acceptcoin\Gateway\Http\Client\ClientMock;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

/**
 * get iframe link
 */
class MockDataRequest implements BuilderInterface
{
    const FORCE_RESULT = 'FORCE_RESULT';

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var MailHelper
     */
    private MailHelper $mailHelper;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Curl                  $curl,
        Session               $checkoutSession,
        ConfigInterface       $config,
        StoreManagerInterface $storeManager,
        MailHelper            $mailHelper,
        ScopeConfigInterface  $scopeConfig
    )
    {
        $this->curl = $curl;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->mailHelper = $mailHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param array $buildSubject
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject): array
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        try {
            $projectId = $this->config->getValue('ac_project_id', $paymentDO->getOrder()->getStoreId());
            $projectSecret = $this->config->getValue('ac_secret_key', $paymentDO->getOrder()->getStoreId());
            $returnUrlSuccess = $this->config->getValue('ac_return_url_success', $paymentDO->getOrder()->getStoreId());
            $returnUrlFailed = $this->config->getValue('ac_return_url_fail', $paymentDO->getOrder()->getStoreId());

            $link = AcceptcoinApi::createRequest(
                $payment->getOrder(),
                $projectId,
                $projectSecret,
                $this->curl,
                $this->storeManager,
                $returnUrlSuccess,
                $returnUrlFailed
            );

            $this->checkoutSession->setacceptcoinIframeLink($link);

            $vendorName = $this->storeManager->getStore($paymentDO->getOrder()->getStoreId())->getName();

            $this->mailHelper->sendMessage(
                $paymentDO->getOrder()->getStoreId(),
                $paymentDO->getOrder()->getShippingAddress()->getEmail(),
                'order_created_template',
                [
                    'subject'   => "Payment created for " . $vendorName,
                    "link"      => $link,
                    "name"      => $payment->getOrder()->getCustomerFirstname(),
                    "lastname"  => $payment->getOrder()->getCustomerLastname(),
                    "amount"    => $payment->getOrder()->getGrandTotal(),
                    "currency"  => $paymentDO->getOrder()->getCurrencyCode(),
                    'storeName' => $vendorName
                ]
            );
        } catch (Throwable $exception) {
            throw new LocalizedException(__($exception->getMessage()));
        }

        return [
            self::FORCE_RESULT => ClientMock::SUCCESS
        ];
    }
}
