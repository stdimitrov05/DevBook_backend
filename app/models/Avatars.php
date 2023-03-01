<?php

namespace App\Models;

class Avatars extends \Phalcon\Mvc\Model
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
     * @var string
     * @Column(type="string", length=20, nullable=false)
     */
    public $user_id;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $name;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $type;

    /**
     *
     * @var integer
     * @Column(type="integer")
     */
    public $size;

    /**
     *
     * @var string
     * @Column(type="string")
     */
    public $path;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSource('avatars');
        $this->belongsTo('user_id', '\App\Models\Users', 'id', [
            'alias' => 'user'
        ]);
    }


}
