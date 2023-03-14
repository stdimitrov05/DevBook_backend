<?php

namespace App\Validation;

use App\Models\Users;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Digit as DigitValidator;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Uniqueness;

class EditUserDetailsValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'username',
            [
                new StringLength([
                    'min' => 3,
                    'messageMinimum' => 'Username must be at least 3 characters.',
                    'max' => 20,
                    'messageMaximum' => 'Username must be at most 20 characters.',
                    'cancelOnFail' => true
                ]),
                new Regex([
                    'pattern' => '/^([a-z]+)(_)?([a-z0-9]+)$/is',
                    'message' => 'Username can only contain a-z, A-Z, 0-9 and "_".',
                    'cancelOnFail' => true
                ]),
                new Uniqueness([
                    'model' => new Users(),
                    'message' => 'Username is already in use.'
                ])
            ]
        );

        $this->rules(
            'location_id',
            [
                new DigitValidator(
                    [
                        "message" => [
                            "height" => "height must be numeric",
                        ],
                    ]
                )
            ]
        );

        $this->rules(
            'description',
            [
                new StringLength([
                    'max' => 100,
                    'messageMaximum' => 'Username must be at most 100 characters.',
                    'cancelOnFail' => true
                ])
            ]
        );

    }

}