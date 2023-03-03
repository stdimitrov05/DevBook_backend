<?php

namespace App\Models;

use App\Lib\Slug;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;

class ForgotPassword extends \Phalcon\Mvc\Model
{
    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=32, nullable=false)
     */
    public $id;

    /**
     *
     * @var integer
     * @Column(type="integer")
     */
    public $user_id;


    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $user_agent;


    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $ip_address;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $token;


    /**
     *
     * @var integer 0 or 1
     * @Column(type="integer", length=1, nullable=false)
     */
    public $confirmed;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=false)
     */
    public $created_at;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSource('forgot_password');
        $this->belongsTo('user_id', '\App\Models\Users', 'id', [
            'alias' => 'user'
        ]);
    }

    public function beforeValidationOnCreate()
    {
        $this->created_at = time();
    }


}
