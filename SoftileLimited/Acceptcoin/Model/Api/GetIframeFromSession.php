<?php

namespace SoftileLimited\Acceptcoin\Model\Api;

use SoftileLimited\Acceptcoin\Api\Web\GetIframeFromSessionInterface;
use Magento\Checkout\Model\Session;

class GetIframeFromSession implements GetIframeFromSessionInterface
{
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->checkoutSession = $session;
    }

    /**
     * @return string
     */
    public function getIframeLink():string
    {
        $iframeLink = $this->checkoutSession->getacceptcoinIframeLink();
        $this->checkoutSession->unsacceptcoinIframeLink();
        return json_encode($iframeLink);
    }
}
