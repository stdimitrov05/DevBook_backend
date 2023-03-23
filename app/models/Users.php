<?php

namespace App\Models;

use App\Lib\Slug;
use Phalcon\Db\Column;
use Phalcon\Mvc\ModelInterface;

class Users extends \Phalcon\Mvc\Model
{
    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=32, nullable=false)
     */
    public ?int $id = null;

    /**
     *
     * @var string
     * @Column(type="string", length=20, nullable=false)
     */
    public string $ip_address ;

    /**
     *
     * @var string
     * @Column(type="string", length=20, nullable=false)
     */
    public string $username;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public string $email;

    /**
     *
     * @var string
     * @Column(type="string", length=255, nullable=false)
     */
    public string $password;

    /**
     *
     * @var float
     * @Column(type="float")
     */
    public ?float $balance = 0.00;

    /**
     *
     * @var integer 0 or 1
     * @Column(type="integer", length=1, nullable=false)
     */
    public ?int $active = 0;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=false)
     */
    public ?int $created_at = null;

    /**
     *
     * @var integer
     * @Column(type="integer", length=11, nullable=true)
     */
    public ?int $deleted_at = null;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSource('users');
    }

    public function beforeValidationOnCreate()
    {
        $this->created_at = time();
    }

    public function beforeCreate()
    {
        $this->password = $this->getDI()->getSecurity()->hash($this->password);
    }

    /**
     * Find user by id
     *
     * @param int $id
     * @return bool|ModelInterface
     */
    public function findById($id)
    {
        return parent::findFirst([
            'columns' => '*',
            'conditions' => 'id = ?1 AND deleted_at IS NULL AND banned = 0',
            'bind' => [1 => $id],
            'bindTypes'  => [Column::BIND_PARAM_INT]
        ]);
    }

}
