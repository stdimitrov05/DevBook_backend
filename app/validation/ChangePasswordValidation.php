<?php
namespace App\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class ChangePasswordValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'password',
            [
                new PresenceOf([
                    'message' => 'Password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    "min" => 6,
                    "messageMinimum" => "Password must be at least 6 characters.",
                ])
            ]
        );

        $this->rules(
            'repeatPassword',
            [
                new PresenceOf([
                    'message' => 'Password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    "min" => 6,
                    "messageMinimum" => "Password must be at least 6 characters.",
                    'cancelOnFail' => true
                ]),
                new Validation\Validator\Confirmation([
                    "message" => "The password confirmation does not match.",
                    "with"    => "password",
                ])
            ]
        );

        $this->rules(
            'token',
            [
                new PresenceOf(['message' => 'Missing token.'])
            ]
        );

    }
}