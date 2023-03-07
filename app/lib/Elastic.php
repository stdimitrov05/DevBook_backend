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
     * updateUserActivateById
     * @param int $userId
     * @retrun  null
     * */

    public function updateUserActivateById(int $userId)
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'type' => 'user',
            'id' => $userId, // Replace with the user ID you want to update
            'body' => [
                'doc' => [
                    'active' => 1 // Replace with the new value you want to set
                ]
            ]
        ];

        $this->elasticsearch->update($requestBody);
    }

    /**
     * deleteUserById
     * @param int $userId
     * @return  bool
     * */

    public function deleteUserById(int $userId): bool
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'type' => 'user',
            'id' => $userId
        ];

        $response = $this->elasticsearch->delete($requestBody);
        // Get the response body as a string
        $response_body = $response->getBody();

        // Parse the response body as JSON
        $search_results = json_decode($response_body, true);

        return $search_results['_shards']['successful'] === 1 ? true : false;

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

    /**
     * getAvatarById
     * @param int $userId
     * @retrun  string | null
     * */

    public function getAvatarById(int $userId): string|null
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'avatars',
            'body' => [
                '_source' => false,
                'query' => [
                    'match' => [
                        'user_id' => $userId // Replace with the user ID you want to get avatars for
                    ]
                ],
                'fields' => ['path']
            ]
        ];

        $response = $this->elasticsearch->search($requestBody);
        $avatars = "";
        foreach ($response['hits']['hits'] as $hit) {
            $avatars = $hit['fields']['path'][0];
        }

        return !$avatars ? null : $avatars;
    }

    /**
     * getUserData
     * @param int $userId
     * @retrun  array
     * */

    public function getUserData(int $userId): array
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'body' => [
                '_source' => true,
                'query' => [
                    'match' => [
                        '_id' => $userId // Replace with the user ID you want to get avatars for
                    ]
                ],
                'fields' => ['*']
            ]
        ];

        $response = $this->elasticsearch->search($requestBody);
        $user = [];
        foreach ($response['hits']['hits'] as $hit) {
            $user['username'] = $hit['fields']['username'][0];
            $user['balance'] = $hit['fields']['balance'][0];
        }
        $user['avatar'] = $this->getAvatarById($userId);

        return !$user ? [] : $user;
    }
}
