<?php
/**
 * Created by PhpStorm.
 * User: luqman
 * Date: 2/25/17
 * Time: 1:33 PM
 */

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use LINE\LINEBot\SignatureValidator;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

require 'vendor/autoload.php';

spl_autoload_register(function ($class_name){
    include  $class_name.'.php';
});

// load config
try{
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}catch (Exception $e){
}

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new Slim\App(['settings' => $config]);
$container = $app->getContainer();

$app->get('/', function (Request $request, Response $response){
    return print_r(User::findOne(['user_id' => 'Ue84692bbf94c980be363679272ec7eb2'])->display_name, 1);

});

$app->get('/profile/{id}', function (Request $request, Response $response, $args){
    $access_token = getenv('CHANNEL_ACCESS_TOKEN');
    $secret = getenv('CHANNEL_SECRET');
    $pass_signature = getenv('PASS_SIGNATURE');

    $http_client = new CurlHTTPClient($access_token);
    $bot = new LINEBot($http_client,['channelSecret' => $secret]);

    $profile = $bot->getProfile($args['id']);

    return print("<pre>".print_r($profile->getJSONDecodedBody(),1)."</pre>");
});

$app->post('/', function (Request $request, Response $response){

    $access_token = getenv('CHANNEL_ACCESS_TOKEN');
    $secret = getenv('CHANNEL_SECRET');
    $pass_signature = getenv('PASS_SIGNATURE');

    // get request body and line signature header
    $body 	   = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);

    // is LINE_SIGNATURE exists in request header?
    if (empty($signature)){
        return $response->withStatus(400, 'Signature not set');
    }

    if(! $pass_signature && SignatureValidator::validateSignature($body,$secret, $signature)){
        return $response->withStatus(400, 'Invalid Signature');
    }

    $http_client = new CurlHTTPClient($access_token);
    $bot = new LINEBot($http_client,['channelSecret' => $secret]);

    $data = json_decode($body,true);
    foreach ($data['events'] as $event) {
        if(! isset($event['source']['userId'])) continue;

        $user_id = $event['source']['userId'];

        if($event['type'] == 'follow'){
            if(User::exist($user_id)){
                $user = User::findOne(['user_id' => $user_id]);
                $result = $bot->replyText($event['replyToken'], "Selamat datang kembali {$user->display_name} :)");

                return $result->getHTTPStatus()." ".$result->getRawBody();

            }else{
                $profile = $bot->getProfile($user_id)->getJSONDecodedBody();
                try{

                    $user = new User();
                    $user->user_id = $user_id;
                    $user->display_name = $profile['displayName'];
                    $user->line_id = 'asdas';
                    $user->insert();
                }catch (Exception $e){
                    $result = $bot->replyText($event['replyToken'], $e->getMessage());

                    return $result->getHTTPStatus()." ".$result->getRawBody();
                }

                $result = $bot->replyText($event['replyToken'], "Halo Kak {$user->display_name}, selamat datang di Flag Quiz!");

                return $result->getHTTPStatus()." ".$result->getRawBody();
            }
        }
    }
});

$app->run();