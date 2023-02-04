<?php

namespace App\Models;

class LoginsHistory extends \Phalcon\Mvc\Model
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
     * @Column(type="integer", length=11, nullable=true)
     */
    public $user_id;

    /**
     *
     * @var string
     * @Column(type="varchar", length=100, nullable=false)
     */
    public $jti;

    /**
     *
     * @var string
     * @Column(type="varchar", length=500, nullable=false)
     */
    public $fcm_token;

    /**
     *
     * @var string
     * @Column(type="varchar", length=39, nullable=true)
     */
    public $ip_address;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=true)
     */
    public $user_agent;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=false)
     */
    public $expire_at;

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
        $this->setSource('logins_history');

        // Defines a n-1 relationship with users
        $this->belongsTo(
            'user_id',
            'App\Models\Users',
            'id',
            [
                'alias' => 'user'
            ]
        );
    }

    public function beforeValidationOnCreate()
    {
        $this->created_at = time();
    }

}
