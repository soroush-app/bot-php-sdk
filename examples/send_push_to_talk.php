<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    list($error, $file_id) = $bot->uploadFile('/path/sound.m4a');

    $file_duration_in_milliseconds = 100000;

    if($error) {
        echo 'File Upload Error, Error : ' . $error . PHP_EOL;
        exit;
    }

    list($error, $success) = $bot->sendPushToTalk($to, '', $file_id, basename('/path/sound.m4a'), filesize('/path/sound.m4a'), $file_duration_in_milliseconds);

    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }

} catch (Exception $e) {
    die($e->getMessage());
}
