<?php

namespace App\Validation;

use App\Models\Users;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\Uniqueness;
use Phalcon\Filter\Validation\Validator\StringLength;

class LoginValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'email',
            [
                new PresenceOf([
                    'message' => 'Email or username is required.',
                    'cancelOnFail' => true
                ]),
                new Email([
                    'message' => 'Enter a valid email.',
                    'cancelOnFail' => true
                ])

            ]
        );


        $this->rules(
            'password',
            [
                new PresenceOf([
                    'message' => 'Password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    'min' => 6,
                    'messageMinimum' => 'Password must be at least 6 characters.'
                ])
            ]
        );


    }

}