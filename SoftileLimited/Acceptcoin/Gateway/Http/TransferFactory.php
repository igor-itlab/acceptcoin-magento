<?php

namespace SoftileLimited\Acceptcoin\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use SoftileLimited\Acceptcoin\Gateway\Request\MockDataRequest;

/**
 * creates transfer object from request data, which will be used by Gateway Client to process requests
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * TransferFactory constructor.
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(TransferBuilder $transferBuilder)
    {
        $this->transferBuilder = $transferBuilder;
    }

    /**
     * @inheritDoc
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setBody($request)
            ->setMethod('POST')
            ->setHeaders(
                [
                    'force_result' => $request[MockDataRequest::FORCE_RESULT] ?? null
                ]
            )
            ->build();
    }
}
