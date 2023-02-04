<?php
namespace App\Validation;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;

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
                new Email(['message' => 'Enter a valid email.'])
            ]
        );
    }
}