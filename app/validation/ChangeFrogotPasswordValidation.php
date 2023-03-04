<?php

namespace App\Validation;

use App\Models\Users;
use Phalcon\Filter\Validation;
use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\Regex;
use Phalcon\Filter\Validation\Validator\StringLength;
use Phalcon\Filter\Validation\Validator\Uniqueness;

class ChangeFrogotPasswordValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'oldPassword',
            [
                new PresenceOf([
                    'message' => 'Old password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    'min' => 6,
                    'messageMinimum' => 'Password must be at least 6 characters.'
                ])
            ]
        );
        $this->rules(
            'newPassword',
            [
                new PresenceOf([
                    'message' => 'New password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    'min' => 6,
                    'messageMinimum' => 'Password must be at least 6 characters.'
                ])
            ]
        );
        $this->rules(
            'currentPassword',
            [
                new PresenceOf([
                    'message' => 'Current password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    'min' => 6,
                    'messageMinimum' => 'Password must be at least 6 characters.'
                ])
            ]
        );

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