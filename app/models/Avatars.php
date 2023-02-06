<?php

namespace App\Models;

use App\Lib\Slug;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;

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
    public $name;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $type;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public $size;


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
        $this->setSource('avatars');
    }

    public function beforeValidationOnCreate()
    {
        $this->created_at = time();
    }

}
