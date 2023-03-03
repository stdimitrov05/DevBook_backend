<?php

namespace App\Lib;

class Helper
{

    /**
     * generateToken
     * Generate email confirm token
     * @return string
     * */
    public static function generateToken() : string
    {
        return bin2hex(random_bytes(16));
    }


}
