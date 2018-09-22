<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    $keyboard_data_2 = $bot->makeKeyboardData('Button 1|Button 2|Button 3' . PHP_EOL . 'Button 2');

     list($error, $success) = $bot->changeKeyboard($to, $keyboard_data_2);

    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }


} catch (Exception $e) {
    die($e->getMessage());
}
