<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;

// Using PDO_MySQL (connecting from App Engine)
$db = new pdo(
    'mysql:unix_socket=/cloudsql/east-retention-db:us-east1:east-retention-db;dbname=retentions',
    'root',  // username
    'e2a2s2t2'       // password
);

// create the Silex application
$app = new Application();
$app->register(new TwigServiceProvider());
$app['twig.path'] = [ __DIR__ ];

$app->get('/', function () use ($app) {
    /** @var PDO $db */
   // $db = $app['database'];
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];

    // Show existing guestbook entries.
    $results = $db->query('SELECT * from bib_info');

    return $twig->render('cloudsql.html.twig', [
        'results' => $results,
    ]);
});



