<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    list($error, $file_id) = $bot->uploadFile('file.zip');

    if($error) {
        echo 'File Upload Error, Error : ' . $error . PHP_EOL;
        exit;
    }

    list($error, $success) = $bot->sendAttachment($to, 'Attachment Caption', $file_id, basename('file.zip'), filesize('file.zip'));

    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }

} catch (Exception $e) {
    die($e->getMessage());
}
