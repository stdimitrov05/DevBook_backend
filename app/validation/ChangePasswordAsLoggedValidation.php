<?php
namespace App\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;

class ChangePasswordAsLoggedValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'currentPassword',
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
            'newPassword',
            [

                new StringLength([
                    "min" => 6,
                    "messageMinimum" => "Password must be at least 6 characters.",
                ])
            ]
        );
        $this->rules(
            'confirmPassword',
            [

                new StringLength([
                    "min" => 6,
                    "messageMinimum" => "Password must be at least 6 characters.",
                ])
            ]
        );
    }
}