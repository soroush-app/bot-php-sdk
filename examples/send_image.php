<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    list($error, $file_id) = $bot->uploadFile('/path/image.png');

    if($error) {
        echo 'File Upload Error, Error : ' . $error . PHP_EOL;
        exit;
    }

    list($error, $success) = $bot->sendImage($to, 'Image Caption', $file_id, basename('/path/image.png'), filesize('/path/image.png'), 512, 512);

    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }

} catch (Exception $e) {
    die($e->getMessage());
}
