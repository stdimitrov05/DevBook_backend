<?php

namespace App\Lib;

class Helper
{
    /**
     * Generates a random string of characters to use as a key.
     *
     * @param int $length The length of the key to generate. Default value is 4.
     * @return string A string containing the random key.
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
     * Converts an IP address string to its varbinary representation.
     * @param string $ipAddress The IP address to convert.
     * @return string A varbinary string representation of the IP address.
     */
    public static function hashIpAddressToVarbinary(string $ipAddress): string
    {
        return inet_pton($ipAddress);
    }
}
