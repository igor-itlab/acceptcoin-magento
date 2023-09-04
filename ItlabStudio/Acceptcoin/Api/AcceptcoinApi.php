<?php

namespace ItlabStudio\Acceptcoin\Api;

use Exception;
use ItlabStudio\Acceptcoin\Services\ACUtils;
use ItlabStudio\Acceptcoin\Services\JWT;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class AcceptcoinApi
{
    public const PREFIX         = "ACMG";
    public const STATUS_PENDING = "pending";

    private const CREATED_STATUS_CODE    = 201;
    public const  PROJECT_ID_SYMBOLS_NUM = 6;

    public const ERROR_MESSAGE = "Acceptcoin payment method is not available at this moment.";

    public const DOMAIN = "https://acceptcoin.io";

    /**
     * @param Order $order
     * @param string $projectId
     * @param string $projectSecret
     * @param Curl $curl
     * @param StoreManagerInterface $storeManager
     * @param string|null $returnUrlSuccess
     * @param string|null $returnUrlFailed
     * @return mixed
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public static function createRequest(
        Order                 $order,
        string                $projectId,
        string                $projectSecret,
        Curl                  $curl,
        StoreManagerInterface $storeManager,
        ?string               $returnUrlSuccess,
        ?string               $returnUrlFailed
    ): mixed
    {
        if (empty($projectId) || empty($projectSecret)) {
            throw new Exception("Missing Accept Coin configuration");
        }

        $referenceId = self::PREFIX . "-" . substr($projectId, 0, self::PROJECT_ID_SYMBOLS_NUM) . "-" . $order->getIncrementId();

        $callbackUrl = $storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . "rest/default/V1/acceptcoin/webhook";

        $url = self::DOMAIN . "/api/iframe-invoices";

        $curl->setHeaders([
            "Accept"        => "application/json",
            "Content-Type"  => "application/json",
            "Authorization" => "JWS-AUTH-TOKEN " . JWT::createToken($projectId, $projectSecret)
        ]);

        $params = [
            "amount"      => (string)ACUtils::convertAmount(
                $order->getBaseGrandTotal(),
                $order->getBaseCurrencyCode(),
                ACUtils::STABLE_CURRENCY_CODE
            ),
            "referenceId" => $referenceId,
            "callBackUrl" => $callbackUrl
        ];

        if ($returnUrlSuccess) {
            $params ["returnUrlSuccess"] = $returnUrlSuccess;
        }

        if ($returnUrlFailed) {
            $params ["returnUrlFail"] = $returnUrlFailed;
        }

        $curl->post($url, json_encode($params));

        $result = $curl->getBody();

        $data = json_decode($result, true);

        if (!$data) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        if ($curl->getStatus() !== self::CREATED_STATUS_CODE) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        if (!isset($data['link'])) {
            throw new Exception(self::ERROR_MESSAGE);
        }

        return $data['link'];
    }
}
