<?php
namespace App\Validation;

use App\Exceptions\HttpExceptions\Http403Exception;
use App\Exceptions\HttpExceptions\Http404Exception;
use App\Exceptions\HttpExceptions\Http500Exception;
use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Confirmation;

class ChangeUserPasswordValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'currentPassword',
            [
                new PresenceOf([
                    'message' => 'Current password is required.',
                    'cancelOnFail' => true
                ]),
                new Validation\Validator\Callback(
                    [
                        'callback' => function($data) {

                            try {
                                $currentPassword = $this->profileService->userPassword($data['userId']);
                            } catch (ServiceException $e) {
                                switch ($e->getCode()) {
                                    case AbstractService::ERROR_USER_NOT_FOUND:
                                        throw new Http404Exception($e->getMessage(), $e->getCode(), $e);
                                    case AbstractService::ERROR_USER_NOT_AUTHORIZED:
                                        throw new Http403Exception($e->getMessage(), $e->getCode(), $e);
                                    default:
                                        throw new Http500Exception('Internal Server Error', $e->getCode(), $e);
                                }
                            }

                            if ($this->security->checkHash($data['currentPassword'], $currentPassword)) {
                                return true;
                            }

                            return false;
                        },
                        'message'  => 'Your current password was incorrect. Please try again.'
                    ]
                )
            ]
        );

        $this->rules(
            'newPassword',
            [
                new PresenceOf([
                    'message' => 'New password is required.',
                    'cancelOnFail' => true
                ]),
                new StringLength(
                    [
                        "min" => 6,
                        "messageMinimum" => "New password must be at least 6 characters",
                    ]
                )
            ]
        );

        $this->rules(
            'confirmPassword',
            [
                new PresenceOf(['message' => 'Repeat new password is required.']),
                new Confirmation(
                    [
                        "message" => "The password confirmation does not match.",
                        "with"    => "newPassword",
                    ]
                )
            ]
        );
    }
}