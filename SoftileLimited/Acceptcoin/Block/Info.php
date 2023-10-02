<?php

namespace SoftileLimited\Acceptcoin\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\SamplePaymentGateway\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
{
    /**
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field): Phrase
    {
        return __($field);
    }

    /**
     * @param string $field
     * @param string $value
     * @return Phrase|string
     */
    protected function getValueView($field, $value): Phrase|string
    {
        if ($field === FraudHandler::FRAUD_MSG_LIST) {
            return implode('; ', $value);
        }

        return parent::getValueView($field, $value);
    }
}
