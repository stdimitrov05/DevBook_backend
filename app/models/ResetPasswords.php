<?php
namespace App\Models;

use App\Lib\Helper;
use Phalcon\Mvc\Model;

/**
 * ResetPasswords
 * Stores the reset password codes and their evolution
 */
class ResetPasswords extends Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $code;

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
     */
    public $created_at;

    /**
     *
     * @var string
     */
    public $reset;

    public function initialize()
    {
        $this->setSource('reset_passwords');

        $this->belongsTo('user_id', '\App\Models\Users', 'id', [
            'alias' => 'user'
        ]);
    }

    /**
     * Before create the user assign a password
     */
    public function beforeValidationOnCreate()
    {
        // Timestamp the reset
        $this->created_at = time();

        // Generate a random confirmation code
        $this->code = Helper::generateToken();

        // Set status to non-confirmed
        $this->reset = 0;
    }

    /**
     * Send an e-mail to users allowing him/her to reset his/her password
     */
    public function afterCreate()
    {
//        $mailer = $this->getDI()->getMailer();
//        $mailer->resetPassword($this->user->email, $this->user->stageName, $this->code);
    }

}
