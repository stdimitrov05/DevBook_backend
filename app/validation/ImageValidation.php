<?php

namespace App\Validation;

use Phalcon\Validation\Validator\File as FileValidator;
use Phalcon\Validation;

class ImageValidation extends Validation
{
    public function initialize()
    {
        $this->rules(
            'file',
            [
                new FileValidator(
                    [
                        "maxSize" => "10M",
                        "allowedTypes" => [
                            "image/jpeg",
                            "image/png",
                        ],

                    ]
                )
            ]
        );


    }
}
