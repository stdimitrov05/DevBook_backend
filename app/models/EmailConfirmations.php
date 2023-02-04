<?php
namespace App\Models;

use Phalcon\Http\Request;
use Phalcon\Mvc\Model;

/**
 * EmailConfirmations
 * This model registers Successful logins registered users have made
 */
class EmailConfirmations extends Model
{
    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=11, nullable=false)
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
     * @Column(type="char", length=32, nullable=false)
     */
    public $token;

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
     * @Column(type="tinyint", length=1, nullable=false)
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
        $this->setSource('email_confirmations');

        $this->belongsTo('user_id', '\App\Models\Users', 'id', [
            'alias' => 'user'
        ]);
    }

    public function beforeValidationOnCreate()
    {
        $this->created_at = time();
    }

    /**
     * Send a confirmation e-mail to the user after create the account
     */
    public function afterCreate()
    {
//        $this->mailer->confirmEmailMessage($this->token, $this->user);
    }

}
