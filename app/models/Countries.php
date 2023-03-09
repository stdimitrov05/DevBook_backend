<?php


namespace App\Models;

use Phalcon\Mvc\Model;

# Countries
# This model is linked to countries
class  Countries extends Model
{
    /**
     * @param int $id
     * @var int AI
     */
    public $id;

    /**
     * @param string $code
     * @var char
     */
    public $code;

    /**
     * @param integer $phone
     * @var integer
     */
    public $phone;

    /**
     * @param string $name
     * @var string(80)
     */
    public $name;

    public function initialize()
    {
        $this->setSource('countries');
    }
}