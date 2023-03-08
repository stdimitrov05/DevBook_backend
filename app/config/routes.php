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

$frontendCollection->get(
    '/countries',
    'getCountriesAction'
);

$app->mount($frontendCollection);

/*============================
Users
=============================*/

$userCollection = new \Phalcon\Mvc\Micro\Collection();
$userCollection->setPrefix(API_VERSION . '/users');
$userCollection->setHandler('\App\Controllers\UsersController', true);

// User Details for home page
$userCollection->get(
    '/{userId:[1-9][0-9]*}/details',
    'userDetailsAction'
);

// Set user billing
$userCollection->post(
    '/{userId:[1-9][0-9]*}/billing',
    'billingAction'
);

// Delete account
$userCollection->delete(
    '/{userId:[1-9][0-9]*}/delete',
    'deleteAction'
);

$app->mount($userCollection);

/*============================
Authentication
=============================*/

$authCollection = new \Phalcon\Mvc\Micro\Collection();
$authCollection->setPrefix(API_VERSION);
$authCollection->setHandler('\App\Controllers\AuthController', true);

// Signup
$authCollection->post(
    '/signup',
    'signupAction'
);
// Login
$authCollection->post(
    '/login',
    'loginAction'
);

// Refresh tokens
$authCollection->get(
    '/refresh/tokens',
    'refreshJWTAction'
);

// Forgot Password
$authCollection->post(
    '/forgot-password',
    'forgotPasswordAction'
);

// Change password after send forgotPassword email
$authCollection->post(
    '/check/forgot-password/token',
    'checkRestPasswordTokenAction'
);

// Change password from reset link
$authCollection->post(
    '/forgot-password/change-password',
    'changeForgotPasswordAction'
);

// Confirm email
$authCollection->post(
    '/users/email/confirm',
    'emailConfirmAction'
);

// Resend confirm email
$authCollection->post(
    '/users/resend/email/confirm',
    'resendEmailConfirmAction'
);

$app->mount($authCollection);


/*============================
Develop
=============================*/

$devCollection = new \Phalcon\Mvc\Micro\Collection();
$devCollection->setPrefix(API_VERSION);
$devCollection->setHandler('\App\Controllers\DevController', true);

// Create users index
$devCollection->post(
    '/elastic/users/index',
    'createUsersAction'
);

// Create avatars index
$devCollection->post(
    '/elastic/avatars/index',
    'createAvatarsAction'
);
// Create user billing index
$devCollection->post(
    '/elastic/users/billing/index',
    'createUserBillingAction'
);


$app->mount($devCollection);

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
