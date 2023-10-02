<?php

namespace SoftileLimited\Acceptcoin\Api\Web;

interface CallbackManagementInterface
{
    /**
     * @return bool
     */
    public function postCallback(): bool;

}
