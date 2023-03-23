<?php

namespace App\Lib;

class Helper
{
    /**
     * Generate captcha code
     * @param int $length
     * @return string
     *
     * @throws \Exception
     */
    public static function randomKeys( int $length = 4): string
    {
        $key = '';
        $pattern = '23456789abcdefghjkmnpqrstuvwxyz';

        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern[mt_rand(0, strlen($pattern) - 1)];
        }

        return $key;
    }

    /**
     * Form ip address convert to varbinary
     * @param string $ipAddress
     * @return  string
     * @example "10.23.23.3" => 0x52a56419
     */
    public static function hashIpAddressToVarbinary(string $ipAddress): string
    {
        return inet_pton($ipAddress);
    }
}
