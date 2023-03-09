<?php

namespace App\Validation;

use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Digit as DigitValidator;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\StringLength;

class BillingValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'location_id',
            [
                new PresenceOf([
                    'message' => 'Email is required.',
                    'cancelOnFail' => true
                ]),
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