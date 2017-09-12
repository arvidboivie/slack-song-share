<?php

require 'vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use SlackSongShare\Helper\SpotifyApiHelper;
use Noodlehaus\Config;

$config = Config::load('config.yml');

$slimConfig = [
    'displayErrorDetails' => true,
    'db' => $config->get('database'),
    'spotify' => $config->get('spotify'),
];

$app = new \Slim\App(['settings' => $slimConfig]);

$container = $app->getContainer();

$container['logger'] = function ($c) {
    $logger = new \Monolog\Logger('logger');
    $file_handler = new \Monolog\Handler\StreamHandler('logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};
$container['db'] = function ($c) {
    $db = $c['settings']['db'];

    $dsn = "mysql:host=".$db['host'].";dbname=".$db['name'].";charset=".$db['charset'];

    $pdo = new PDO($dsn, $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$app->get('/spotify/auth/', function (Request $request, Response $response) {
    $spotify = $this->get('settings')['spotify'];

    $api = (new SpotifyApiHelper(
        $this->db,
        $spotify['client_id'],
        $spotify['client_secret'],
        $spotify['redirect_URI']
    ))->getApiWrapper();

    $queryString = $request->getQueryParams();

    if (empty($queryString['code']) === true) {
        $authorizeUrl = $api->getAuthorizeUrl([
            'playlist-read-private',
            'playlist-read-collaborative',
        ]);

        return $response->withRedirect($authorizeUrl, 302);
    }

    $status = $api->getAccessToken($queryString['code']);

    $response->getBody()->write('Auth successful');

    return $response;
});

$app->run();
