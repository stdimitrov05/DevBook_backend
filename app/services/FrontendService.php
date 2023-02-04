<?php

namespace App\Services;

/**
 * Business-logic for site frontend
 *
 * Class FrontendService
 */
class FrontendService extends AbstractService
{

    /**
     * Index
     *
     * @return array
     */
    public function index()
    {
        return [
            'status' => 'DevBook.net api work'
        ];
    }

}
