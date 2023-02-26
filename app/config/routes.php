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
