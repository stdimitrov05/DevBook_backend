<?php

namespace App\Lib;


use App\Exceptions\ServiceException;
use App\Services\AbstractService;
use Phalcon\Encryption\Security\JWT\Builder;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use Phalcon\Encryption\Security\JWT\Signer\Hmac;
use Phalcon\Encryption\Security\JWT\Token\Parser;
use Phalcon\Encryption\Security\JWT\Token\Token;
use Phalcon\Encryption\Security\JWT\Validator;

class JWT extends AbstractService
{
    private const ALGO = "sha512";

    /**
     * Generates JWT access and refresh tokens for a given user ID.
     * @param int $userId The ID of the user for whom the tokens are being generated.
     * @param int $remember Flag indicating whether the user has clicked on "remember me" option. Defaults to 0.
     * @return array An array containing the generated access token, refresh token, and their respective expiration times.
     * @throws ServiceException If either the access token or the refresh token cannot be created.
     * @throws \RedisException
     * @throws ValidatorException
     */
    public function generateTokens(int $userId, int $remember = 0): array
    {
        // Generate unique jti (JWT ID) using openssl_random_pseudo_bytes function and base64 encode it
        $jti = base64_encode(openssl_random_pseudo_bytes(32));
        // Create a new instance of the Hmac signer class using the configured algorithm (default is 'sha512')
        $signer = new Hmac(self::ALGO);
        // Set the issued at time (iat) to the current Unix timestamp
        $iat = time();
        // Get the issuer (iss) from the configured domain in the application settings
        $iss = $this->config->application->domain;
        // Set the expiration time (exp) to the current Unix timestamp plus the configured access token expiry time
        $exp = $iat + $this->config->auth->accessTokenExpire;

        // Create an access token with the specified properties
        $accessToken = (new Builder($signer))
            ->setExpirationTime($exp)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setSubject($userId)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            // Get token object
            ->getToken()
            // Get token string
            ->getToken();

        // Determine the refresh token expiry time based on whether the "remember me" option was selected
        $refreshExpire = $remember == 1
            ? $this->config->auth->refreshTokenRememberExpire
            : $this->config->auth->refreshTokenExpire;

        // Create a refresh token with the specified properties
        $refreshToken = (new Builder($signer))
            ->setExpirationTime($iat + $refreshExpire)
            ->setPassphrase($this->config->auth->key)
            ->setNotBefore($iat)
            ->setId($jti)
            ->setIssuer($iss)
            ->setIssuedAt($iat)
            ->setSubject($userId)
            // Get token object
            ->getToken()
            // Get token string
            ->getToken();

        // If the refresh or access token could not be created, throw a ServiceException with an appropriate
        if (!$accessToken || !$refreshToken) {
            throw  new ServiceException(
                'Unable to create JWT tokens',
                self::ERROR_UNABLE_TO_CREATE
            );
        }

        // Store the JWT refresh token jti in Redis with the user ID as the key and the configured refresh token expiry time as the TTL
        $this->redisService->storeJti($userId, $jti, $refreshExpire);

        return [
            "accessToken" => $accessToken,
            "refreshToken" => $refreshToken,
            'expireAt' => $iat + $refreshExpire,
        ];
    }

    /**
     * Decodes a JWT token string and returns its object representation.
     * @param string $token The token string to decode.
     * @return object The decoded token object.
     * @throws ServiceException If the token is invalid or cannot be parsed.
     */
    public function decode(string $token): object
    {
        try {
            // Parse the given JWT token using the Parser class and return the result.
            $parser = new Parser();
            return $parser->parse($token);
        } catch (\InvalidArgumentException $exception) {
           // If the token is invalid or malformed, throw a ServiceException with an appropriate error message.
            throw new ServiceException(
                'Bad token',
                self::ERROR_BAD_TOKEN
            );
        }
    }

    /**
     *Validates a JWT token object and throws an exception if it is invalid.
     * @param Token $token The token object to validate.
     * @throws ServiceException If the token is invalid or cannot be parsed.
     * @throws ValidatorException
     */
    public function validateJwt(Token $token): void
    {
        $validator = new Validator($token);
        $signer = new Hmac(self::ALGO);;

        // Validate token
        $validator
            ->validateSignature($signer, $this->config->auth->key)
            ->validateIssuer($this->config->application->domain)
            ->validateExpiration(time());

        if ($validator->getErrors()) {
            throw new ServiceException(
                'Bad token',
                self::ERROR_BAD_TOKEN
            );
        }
    }

    /**
     * Extracts the authorization token from the request header.
     * @return string|bool The authorization token string if found, otherwise false.
     */
    public function getAuthorizationToken(): bool|string
    {
        // Get jwt (refreshToken) from headers
        $authorizationHeader = $this->request->getHeader('Authorization');

        if ($authorizationHeader and preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return $matches[1];
        } else {
            return false;
        }
    }


}