<?php

namespace App\Lib;

use App\Exceptions\ServiceException;
use App\Services\AbstractService;

class Elastic extends AbstractService
{
    private object $elasticsearch;

    public function __construct($elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * createUsersIndex
     * @uses  \Elastic\Elasticsearch\ClientBuilder
     * @retrun  null
     */
    public function createUsersIndex()
    {
        // Create params
        $params = [
            'index' => 'users',
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer'
                        ],
                        'username' => [
                            'type' => 'keyword'
                        ],
                        'email' => [
                            'type' => 'keyword'
                        ],
                        'password' => [
                            'type' => 'keyword'
                        ],
                        'balance' => [
                            'type' => 'integer'
                        ],
                        'active' => [
                            'type' => 'integer'
                        ],
                        'created_at' => [
                            'type' => 'date',
                            'format' => 'epoch_second'
                        ],
                        'deleted_at' => [
                            'type' => 'date',
                            'format' => 'epoch_second'
                        ]
                    ]
                ]
            ]
        ];

        $this->elasticsearch->indices()->create($params);
        // Check if the index exists
        if (!$this->elasticsearch->indices()->exists(['index' => 'users'])) {
            throw new ServiceException('Unable to create index',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        return null;
    }

    /**
     * insertUserData
     * @param array $userData
     * @retrun  null
     * */

    public function insertUserData(array $userData)
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'id' => $userData['id'], // ID of the document you are inserting
            'body' => [
                "username" => $userData['username'],
                "email" => $userData['email'],
                "password" => $userData['password'],
                "balance" => $userData['balance'],
                "active" => $userData['active'],
                "created_at" => $userData['created_at'],
                "deleted_at" => $userData['deleted_at']
            ]
        ];


        $this->elasticsearch->index($requestBody);
    }

    /**
     * createAvatarIndex
     * @uses  \Elastic\Elasticsearch\ClientBuilder
     * @retrun  null
     */
    public function createAvatarIndex()
    {
        // Create params
        $params = [
            'index' => 'avatars',
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer'
                        ],
                        'user_id' => [
                            'type' => 'integer'
                        ],
                        'name' => [
                            'type' => 'keyword'
                        ],
                        'type' => [
                            'type' => 'keyword'
                        ],
                        'size' => [
                            'type' => 'integer'
                        ],
                        'path' => [
                            'type' => 'keyword',
                        ],
                    ]
                ]
            ]
        ];

        $this->elasticsearch->indices()->create($params);
        // Check if the index exists
        if (!$this->elasticsearch->indices()->exists(['index' => 'avatars'])) {
            throw new ServiceException('Unable to create index',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        return null;
    }


    /**
     * insertAvatarData
     * @param array $avatarData
     * @retrun  null
     * */

    public function insertAvatarData(array $avatarData)
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'avatars',
            'id' => $avatarData['id'], // ID of the document you are inserting
            'body' => [
                "user_id" => $avatarData['user_id'],
                "name" => $avatarData['name'],
                "type" => $avatarData['type'],
                "size" => $avatarData['size'],
                "path" => $avatarData['path'],
            ]
        ];


        $this->elasticsearch->index($requestBody);
    }

}
