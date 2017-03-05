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
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

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
    ini_set('display_errors', 1);
    $user = User::findOne(['user_id' => 'Ue84692bbf94c980be363679272ec7eb2']);
    die(print_r(User::getTopTen(),1 ));

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
    if($pass_signature == 'false' && ! SignatureValidator::validateSignature($body,$secret, $signature)){
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
                $bot->pushMessage($user_id, new TextMessageBuilder("Selamat datang kembali {$user->display_name} :)"));
                $bot->pushMessage($user_id, new StickerMessageBuilder(1, 4));
                $bot->pushMessage($user_id, Question::getMenu());
//                return $result->getHTTPStatus()." ".$result->getRawBody();

            }else{
                $profile = $bot->getProfile($user_id)->getJSONDecodedBody();
                try{

                    $user = new User();
                    $user->user_id = $user_id;
                    $user->display_name = $profile['displayName'];
                    $user->line_id = 'asdas';
                    $user->insert();
                    $bot->pushMessage($user_id, new LINEBot\MessageBuilder\TextMessageBuilder("Halo Kak {$user->display_name}, selamat datang di Flag Quiz!"));
                    $bot->pushMessage($user_id, new StickerMessageBuilder(1, 13));
                    $bot->pushMessage($user_id, new TextMessageBuilder('Dalam kuis ini Kakak akan diberikan pertanyaan-pertanyaan mudah terkait bendera yang ditampilkan'));
                    $bot->pushMessage($user_id, Question::getMenu());

//                    return $result->getHTTPStatus()." ".$result->getRawBody();
                }catch (Exception $e){
                    $result = $bot->replyText($event['replyToken'], $e->getMessage());

                    return $result->getHTTPStatus()." ".$result->getRawBody();
                }
            }
        }elseif($event['type'] == 'message'){
            $text = strtolower($event['message']['text']);
            $user = User::findOne(['user_id' => $user_id]);
            if($text == "mulai"){
                if($user->current_score > 0){
                    $bot->pushMessage($user_id, new TextMessageBuilder("Kakak kan sudah mulai, gimana sih..."));
                    $bot->pushMessage($user_id, new StickerMessageBuilder(1, 405));
                }else{
                    $question = new Question($user);

                    $bot->pushMessage($user_id, $question->generate());
                }
            }else{
                if($text == "menu"){
                    $bot->pushMessage($user_id, Question::getMenu());
                }elseif ($text == "hi_score"){
                    $bot->pushMessage($user_id, new TextMessageBuilder("Skor tertinggi Kakak adalah {$user->high_score}"));
                    $bot->pushMessage($user_id, Question::getMenu());
                }elseif ($text == "global_rank"){
                    $bot->pushMessage($user_id, User::getTopTen());
                    $bot->pushMessage($user_id, Question::getMenu());
                }elseif($text == $user->answer_needed){
                    $user->current_score = $user->current_score + 1;
                    $user->save();
                    $bot->pushMessage($user_id, new TextMessageBuilder("Jawaban Kakak benar!"));
                    $bot->pushMessage($user_id, new StickerMessageBuilder(2, 144));
                    $bot->pushMessage($user_id, new TextMessageBuilder("Lanjut ke pertanyaan berikutnya ya Kak"));
                    $question = new Question($user);
                    $bot->pushMessage($user_id, $question->generate());
                }else{
                    switch ($user->life){
                        case 5:
                            $text = "Wah, salah Kak jawabannya :(";
                            $sticker = new StickerMessageBuilder(1, 403);
                            break;
                        case 4:
                            $text = "Kok salah lagi sih? Ini gampang loh";
                            $sticker = new StickerMessageBuilder(1, 117);
                            break;
                        case 3:
                            $text = "Dulu sekolah dimana sih? Ginian aja kok gak bisa";
                            $sticker = new StickerMessageBuilder(1, 104);
                            break;
                        case 2:
                            $text = "Ya ampun salah lagi. Tapi beneran pernah sekolah kan?";
                            $sticker = new StickerMessageBuilder(1, 101);
                            break;
                        default:
                            $text = "Ah, sudahlah...";
                            $sticker = new StickerMessageBuilder(2, 18);
                    }
                    $user->life = $user->life - 1;
                    $text2 = "Kakak hanya boleh salah menjawab {$user->life} kali lagi";
                    if($user->life < 1){
                        $text2 = "Game over. Skor Kakak adalah {$user->current_score}.";
                        $bot->pushMessage($user_id, new TextMessageBuilder($text));
                        $bot->pushMessage($user_id, $sticker);
                        $bot->pushMessage($user_id, new TextMessageBuilder($text2));

                        if($user->current_score > $user->high_score){
                            $bot->pushMessage($user_id, new TextMessageBuilder("Ini skor tertinggi Kakak! Mengalahkan skor sebelumnya yaitu {$user->high_score}"));
                            $user->high_score = $user->current_score;
                        }
                        $user->current_score = 0;
                        $user->life = 5;
                        $user->answered = '';
                        $user->answer_needed = '';

                        $bot->pushMessage($user_id, Question::getMenu());
                    }else{
                        $bot->pushMessage($user_id, new TextMessageBuilder($text));
                        $bot->pushMessage($user_id, $sticker);
                        $bot->pushMessage($user_id, new TextMessageBuilder($text2));
                        $bot->pushMessage($user_id, Question::deserializeQuestion($user->last_question));
                    }
                    $user->save();
                }
            }
        }else{
            $result = $bot->replyText($event['replyToken'], print_r($event, 1));

            return $result->getHTTPStatus()." ".$result->getRawBody();
        }
    }
});

$app->run();