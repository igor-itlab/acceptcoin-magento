<?php

namespace SoftileLimited\Acceptcoin\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment Gateway Code
     */
    const CODE = 'acceptcoin';

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'acceptCoinIcon' => "https://acceptcoin.io/assets/images/logo50.png"
                ]
            ]
        ];
    }
}
