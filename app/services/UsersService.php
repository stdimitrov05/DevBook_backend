<?php

namespace App\Services;

use App\Exceptions\ServiceException;
use App\Models\Avatars;
use App\Models\Users;
use Intervention\Image\ImageManager;

/**
 * Business-logic for users
 *
 * Class UsersService
 */
class UsersService extends AbstractService
{

    /**
     * Creating a new user
     *
     * @param array $data
     * @return array
     */
    public function createUser(array $data)
    {

        try {
            $this->db->begin();
            $user = new Users();
            $user->assign($data);
            $result = $user->create();

            if (!$result) {
                throw new ServiceException(
                    'Unable to create user',
                    self::ERROR_UNABLE_CREATE_AVATAR
                );
            }

//
//            $ipAddress = $this->request->getClientAddress();
//            $userAgent = $this->request->getUserAgent();
//            $token = Helper::generateToken();

//            // Send email with confirmation link
//            $emailConfirmation = new EmailConfirmations();
//            $emailConfirmation->user_id = $user->id;
//            $emailConfirmation->token = $token;
//            if ($ipAddress) $emailConfirmation->ip_address = $ipAddress;
//            if ($userAgent) $emailConfirmation->user_agent = $userAgent;
//            $emailConfirmation->save();
//
            $this->db->commit();

        } catch (\PDOException $e) {
            $this->db->rollback();
            throw new ServiceException($e->getMessage(), $e->getCode(), $e);
        }

        return [
            'userId' => $user->id
        ];

    }
    /**
     * Change Password
     * @param array $data
     * @return  array
     */
    public function changePassword(array $data)
    {
        $userId = $this->authService->getIdentity();
        $currentPassword = $data['currentPassword'];
        $newPassword = $data['newPassword'];
        $confirmPassword = $data['confirmPassword'];

        $sql = "SELECT password FROM users WHERE id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam("userId", $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $userPassword = $stmt->fetchColumn();


        if (!$userPassword) {
            throw new ServiceException(
                "The password you entered does not exist",
                self::ERROR_PASSWORD_NOT_FOUND
            );
        }
        if (!$this->security->checkHash($currentPassword, $userPassword)) {
            throw new ServiceException("You entered an incorrect password", self::ERROR_PASSWORD_INCORRECT);
        }

        if ($newPassword != $confirmPassword){
            throw new ServiceException(
                "The passwords do not match",
                self::ERROR_PASSWORD_NOT_MATCH
            );
        }

        $hashNewPass = $this->security->hash($newPassword);

        $sql = "UPDATE users SET password =:newPass WHERE id =:userId";
        $db = $this->db->prepare($sql);
        $db->bindParam('newPass', $hashNewPass, \PDO::PARAM_STR);
        $db->bindParam('userId', $userId, \PDO::PARAM_INT);
        $db->execute();


        return null;
    }

}
