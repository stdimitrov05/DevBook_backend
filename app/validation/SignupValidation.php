<?php
namespace App\Validation;

use App\Models\Users;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Uniqueness;

class SignupValidation extends Validation
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
                ]),
                new Uniqueness([
                    'model'   => new Users(),
                    'message' => 'Email address is already in use.'
                ])
            ]
        );

        $this->rules(
            'username',
            [
                new PresenceOf([
                    'message' => 'Username is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength([
                    'min' => 3,
                    'messageMinimum' => 'Username must be at least 3 characters.',
                    'max' => 20,
                    'messageMaximum' => 'Username must be at most 20 characters.',
                    'cancelOnFail' => true
                ]),
                new Validation\Validator\Regex([
                    'pattern' => '/^([a-z]+)(_)?([a-z0-9]+)$/is',
                    'message' => 'Username can only contain a-z, A-Z, 0-9 and "_".',
                    'cancelOnFail' => true
                ]),
                new Uniqueness([
                    'model'   => new Users(),
                    'message' => 'Username is already in use.'
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
