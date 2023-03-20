<?php

namespace App\Lib;

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
     */
    public function createUsersIndex(): void
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
    }

    /**
     * createUserBillingIndex
     * @uses  \Elastic\Elasticsearch\ClientBuilder
     */
    public function createUserBillingIndex(): void
    {
        // Create params
        $params = [
            'index' => 'user_billing',
            'body' => [
                'mappings' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer'
                        ],
                        'user_id' => [
                            'type' => 'integer',
                        ],
                        'location_id' => [
                            'type' => 'integer'
                        ],
                        'description' => [
                            'type' => 'keyword'
                        ]
                    ]
                ]
            ]
        ];

        $this->elasticsearch->indices()->create($params);
    }

    /**
     * createAvatarIndex
     * @uses  \Elastic\Elasticsearch\ClientBuilder
     */
    public function createAvatarIndex(): void
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
    }
    /**
     * insertUserBilling
     * @param int $billingId
     * @param array $data
     * @retrun  null
     * */
    public function insertUserBilling(int $billingId, array $data): void
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'user_billing',
            'id' => $billingId, // ID of the document you are inserting
            'body' => [
                "user_id" => $data['user_id'],
                "location_id" => $data['location_id'],
                "description" => $data['description'],
            ]
        ];

        $this->elasticsearch->index($requestBody);
    }


    /**
     * insertUserData
     * @param array $userData
     */
    public function insertUserData(array $userData): void
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
     */
    public function updateUserActivateById(int $userId): void
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
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
     * updateUserActivateById
     * @param int $userId
     * @param string $password
     * @retrun  null
     */
    public function updateUserPassword(int $userId, string $password): void
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'id' => $userId, // Replace with the user ID you want to update
            'body' => [
                'doc' => [
                    'password' => $password // Replace with the new value you want to set
                ]
            ]
        ];
        $this->elasticsearch->update($requestBody);
    }

    /**
     * updateBilling
     * @param int $billingId
     * @param array $data
     * @retrun  null
     */
    public function updateBilling(int $billingId, array $data): void
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'user_billing',
            'id'=>$billingId,
            'body' => [
                'doc' => [
                    'location_id' => $data['location_id'], // Replace with the new value you want to set
                    'description' => $data['description'], // Replace with the new value you want to set
                ]
            ]
        ];
        $this->elasticsearch->update($requestBody);
    }


    /**
     * updateUsername
     * @param int $userId
     * @param string $username
     * */
    public function updateUsername(int $userId, string $username) : void
    {
        $requestBody = [];

        $requestBody = [
            'index' => 'users',
            'id' => $userId, // Replace with the user ID you want to update
            'body' => [
                'doc' => [
                    'username' => $username // Replace with the new value you want to set
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
     * insertAvatarData
     * @param array $avatarData
     */

    public function insertAvatarData(array $avatarData): void
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
     * getUserData
     * @param int $userId
     * @retrun  array
     * */

    public function getUserData(int $userId): array
    {
        $requestBody = [];

        $requestBody = [
            'body' => [
                // First search request
                [],
                ['query' => ['match' => ['_id' => $userId]]],
                // Second search request
                ['index' => 'user_billing'],
                ['query' => ['match' => ['user_id' => $userId]]],
                // Third search request
                ['index' => 'avatars'],
                ['query' => ['match' => ['user_id' => $userId]]],
            ]
        ];

        $response = $this->elasticsearch->msearch($requestBody);
        // Get response length
        $count = $response['responses'][0]['_shards']['total'];
        $user = [];
        // Mapping response data
        for ($i = 0; $i < $count; $i++) {
            foreach ($response['responses'][$i]['hits']['hits'] as $hit) {
                $user[$hit['_index']] = $hit['_source'];

            }
        }

        return $user;
    }

}
