<?php
/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 2/25/17
 * Time: 1:33 PM
 */

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db'] = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'kodokbangkok',
    'dbname' => 'flag_quiz'
];

$app = new Slim\App(['settings' => $config]);
$container = $app->getContainer();

$container['db'] = function ($c){
    $db = $c['settings']['db'];
    $pdo = new PDO("mysql:host=".$db['host'].";dbname=".$db['dbname'], $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$app->get('/', function (Request $request, Response $response){
    $db = $this->db;
    $statement = $db->query('select * from countries');

    return print_r($statement->fetchAll(PDO::FETCH_ASSOC), 1);
});

$app->post('/', function (Request $request, Response $response){

});

$app->run();