<?php
namespace App\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class LoginValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'email',
            [
                new PresenceOf([
                    'message' => 'Email or Username is required.',
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
                new StringLength(
                    [
                        "min" => 6,
                        "messageMinimum" => "At least 6 characters"
                    ]
                )
            ]
        );

        // Remember me accepts 0 or 1
        $this->rules(
            'remember',
            [
                new Validation\Validator\InclusionIn(
                    [
                        "message" => "Remember me is not valid.",
                        "domain"  => [0, 1]
                    ]
                )
            ]
        );
    }
}