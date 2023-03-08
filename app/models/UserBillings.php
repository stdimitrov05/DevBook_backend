<?php


namespace App\Models;

use Phalcon\Mvc\Model;

# UserBillings
class  UserBillings extends Model
{
    /**
     * @param int $id
     * @var int AI
     */
    public $id;

    /**
     * @param int $user_id
     * @var int
     */
    public $user_id;

    /**
     * @param integer $location_id
     * @var integer
     */
    public $location_id;

    /**
     * @param string $description
     * @var string(200)
     */
    public $description;

    public function initialize()
    {
        $this->setSource('user_billing');
        $this->belongsTo('user_id', '\App\Models\Users', 'id', [
            'alias' => 'user'
        ]);
        $this->belongsTo('location_id', '\App\Models\Countries', 'id', [
            'alias' => 'country'
        ]);
    }
}