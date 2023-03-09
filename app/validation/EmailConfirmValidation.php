<?php

namespace App\Validation;

use App\Models\Users;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Uniqueness;

class EmailConfirmValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'token',
            [
                new PresenceOf([
                    'message' => 'Token is required.',
                    'cancelOnFail' => true
                ])
            ]
        );
    }

}