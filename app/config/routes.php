<?php

/*============================
Frontend
=============================*/

$frontendCollection = new \Phalcon\Mvc\Micro\Collection();
$frontendCollection->setPrefix(API_VERSION)
    ->setHandler('\App\Controllers\FrontendController', true)
    ->get('/', 'index')
    ->get('/locations', 'getLocations');

$app->mount($frontendCollection);

/*============================
Authentication
=============================*/

$authCollection = new \Phalcon\Mvc\Micro\Collection();
$authCollection->setPrefix(API_VERSION)
    ->setHandler('\App\Controllers\AuthController', true)
    ->post('/signup', 'signup')
    ->get('/signup', 'getSignupData');
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
