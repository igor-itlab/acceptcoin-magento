<?php

namespace SoftileLimited\Acceptcoin\Services;

class Signature
{
    /**
     * @param string $data
     * @param string $signature
     * @param string $key
     * @return bool
     */
    public static function check(string $data, string $signature, string $key): bool
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true)) == $signature;
    }
}
