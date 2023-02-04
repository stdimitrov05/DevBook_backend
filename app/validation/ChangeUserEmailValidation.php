<?php
namespace App\Validation;

use App\Models\Users;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Uniqueness;

class ChangeUserEmailValidation extends Validation
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
                new Validation\Validator\Callback([
                    'callback' => function($data) {
                        $currentPassword = $this->profileService->userPassword($data['userId']);

                        if ($this->security->checkHash($data['password'], $currentPassword)) {
                            return true;
                        }

                        return false;
                    },
                    'message'  => 'Your password was incorrect. Please try again.'
                ])
            ]
        );

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
                ]),
                new Uniqueness([
                    "model"   => new Users(),
                    "message" => "Email address is already in use.",
                ])
            ]
        );

        $this->rules(
            'confirmEmail',
            [
                new PresenceOf([
                    'message' => 'Email confirmation is required.',
                    'cancelOnFail' => true
                ]),
                new Email([
                    'message' => 'Enter a valid email.',
                    'cancelOnFail' => true
                ]),
                new Validation\Validator\Confirmation([
                    "message" => "The email confirmation does not match.",
                    "with"    => "email"
                ])

            ]
        );
    }
}