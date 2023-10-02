<?php

namespace SoftileLimited\Acceptcoin\Services;

use Exception;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Throwable;

class ACUtils
{
    public const FLOW_DATA_PROCESSED_AMOUNT = "processedAmountInUSD";

    public const STABLE_CURRENCY_CODE = "USD";

    /**
     * @param array $data
     * @return float
     */
    public static function getProcessedAmount(array $data): float
    {
        if (!isset($data['flowData'])) {
            return 0;
        }

        $processedAmount = array_filter($data['flowData'], function ($item) {
            return isset($item['name']) && $item['name'] === self::FLOW_DATA_PROCESSED_AMOUNT;
        });

        if (!count($processedAmount)) {
            return 0;
        }

        return $processedAmount[array_key_first($processedAmount)]['value'];
    }

    /**
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     * @throws Exception
     */
    public static function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        try {
            /** @var CurrencyFactory $currencyFactory */
            $currencyFactory = ObjectManager::getInstance()->get(CurrencyFactory::class);

            $baseCurrency = $currencyFactory->create()->load($fromCurrency);
            $targetCurrency = $currencyFactory->create()->load($toCurrency);

            if ($baseCurrency && $targetCurrency) {
                return $baseCurrency->convert($amount, $targetCurrency);
            }

            return null;
        } catch (Throwable $throwable) {
            throw new Exception($throwable->getMessage());
        }
    }
}
