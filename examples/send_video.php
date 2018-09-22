<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    list($error, $file_id) = $bot->uploadFile('/path/file.mp4');

    $video_duration_in_milliseconds = 15000;

    if($error) {
        echo 'File Upload Error, Error : ' . $error . PHP_EOL;
        exit;
    }

    list($error, $success) = $bot->sendVideo($to, '', $file_id, basename('/path/file.mp4'), filesize('/path/file.mp4'), $video_duration_in_milliseconds,640,480);

    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }

} catch (Exception $e) {
    die($e->getMessage());
}
