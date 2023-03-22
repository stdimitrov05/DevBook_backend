<?php

/*============================
Frontend
=============================*/

$frontendCollection = new \Phalcon\Mvc\Micro\Collection();
$frontendCollection->setPrefix(API_VERSION);
$frontendCollection->setHandler('\App\Controllers\FrontendController', true)
    ->get('/', 'indexAction')
    ->get('/locations', 'getLocations');

$app->mount($frontendCollection);

/*============================
Users
=============================*/

$userCollection = new \Phalcon\Mvc\Micro\Collection();
$userCollection->setPrefix(API_VERSION . '/users')
    ->setHandler('\App\Controllers\UsersController', true)
    // Get user details
    ->get('/{userId:[1-9][0-9]*}/details', 'userDetailsAction')
    // Set user billing
    ->post('/{userId:[1-9][0-9]*}/billing', 'billingAction')
    // Update user account
    ->put('/{userId:[1-9][0-9]*}/edit/details', 'updateDetailsAction')
    // Delete account
    ->delete('/{userId:[1-9][0-9]*}/delete', 'deleteAction')
    // Upload avatar
    ->post('/{userId:[1-9][0-9]*}/avatar/upload', 'uploadAvatarAction');

$app->mount($userCollection);

/*============================
Authentication
=============================*/

$authCollection = new \Phalcon\Mvc\Micro\Collection();
$authCollection->setPrefix(API_VERSION)
    ->setHandler('\App\Controllers\AuthController', true)
    ->post('/signup', 'signupAction')
    ->post('/login', 'loginAction')
    ->get('/refresh/tokens', 'refreshJWTAction')
    ->post('/forgot-password', 'forgotPasswordAction')
    // Change password after send forgotPassword email
    ->post('/check/forgot-password/token', 'checkRestPasswordTokenAction')
    // Change password from reset link
    ->post('/forgot-password/change-password', 'changeForgotPasswordAction')
    ->post('/users/email/confirm', 'emailConfirmAction')
    ->post('/users/resend/email/confirm', 'resendEmailConfirmAction');

$app->mount($authCollection);

// Not found URLs
$app->notFound(
    function () use ($app) {
        throw  new \Exception('URI not found: ' . $app->request->getMethod() . ' ' . $app->request->getURI());
//        throw new \App\Exceptions\HttpExceptions\Http404Exception(
//            'URI not found or error in request.',
//            \App\Controllers\AbstractController::ERROR_NOT_FOUND,
//            new \Exception('URI not found: ' . $app->request->getMethod() . ' ' . $app->request->getURI())
//        );
    }
);
