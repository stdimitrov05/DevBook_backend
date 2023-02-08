<?php

/*============================
Frontend
=============================*/

$frontendCollection = new \Phalcon\Mvc\Micro\Collection();
$frontendCollection->setPrefix(API_VERSION);
$frontendCollection->setHandler('\App\Controllers\FrontendController', true);

$frontendCollection->get(
    '/',
    'indexAction'
);

$app->mount($frontendCollection);


/*============================
Avatar
=============================*/

$avatarCollection = new \Phalcon\Mvc\Micro\Collection();
$avatarCollection->setPrefix(API_VERSION . '/avatars');
$avatarCollection->setHandler('\App\Controllers\AvatarController', true);

// Uploaded avatar
$avatarCollection->post(
    '/upload',
    'uploadAction'
);


$app->mount($avatarCollection);


/*============================
Users
=============================*/

$usersCollection = new \Phalcon\Mvc\Micro\Collection();
$usersCollection->setPrefix(API_VERSION . '/users');
$usersCollection->setHandler('\App\Controllers\UsersController', true);

// Uploaded avatar
$usersCollection->post(
    '/{id:[1-9][0-9]*}/change-password',
    'changePasswordAction'
);


$app->mount($usersCollection);


/*============================
Authentication
=============================*/

$authCollection = new \Phalcon\Mvc\Micro\Collection();
$authCollection->setHandler('\App\Controllers\AuthController', true);
$authCollection->setPrefix(API_VERSION);

// Sign up
$authCollection->post(
    '/signup',
    'signupAction'
);

// Confirm email
$authCollection->post(
    '/users/email/confirm',
    'confirmEmailAction'
);

// Resend confirmation email
$authCollection->post(
    '/users/email/resend-confirmation',
    'resendConfirmationEmailAction'
);

// Login
$authCollection->post(
    '/login',
    'loginAction'
);

// Refresh JWT tokens
$authCollection->get(
    '/token/refresh',
    'refreshJwtTokensAction'
);

// Forgot password
$authCollection->post(
    '/users/password/forgot',
    'forgotPasswordAction'
);

// Verify password reset token
$authCollection->post(
    '/users/password/verify-token',
    'verifyResetPasswordTokenAction'
);

// Change password
$authCollection->post(
    '/users/password/change',
    'changePasswordAction'
);

$app->mount($authCollection);

// Not found URLs
$app->notFound(
  function () use ($app) {
      $exception =
        new \App\Exceptions\HttpExceptions\Http404Exception(
          'URI not found or error in request.',
          \App\Controllers\AbstractController::ERROR_NOT_FOUND,
          new \Exception('URI not found: ' . $app->request->getMethod() . ' ' . $app->request->getURI())
        );
      throw $exception;
  }
);
