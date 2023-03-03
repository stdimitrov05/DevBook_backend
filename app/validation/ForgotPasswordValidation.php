<?php

namespace App\Validation;

use App\Models\Users;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Uniqueness;

class ForgotPasswordValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'email',
            [
                new PresenceOf([
                    'message' => 'Email is required.',
                    'cancelOnFail' => true
                ]),
                new Email([
                    'message' => 'Enter a valid email.',
                    'cancelOnFail' => true
                ])
            ]
        );


    }

}