<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $file_id = 'received file id';

    $save_path = dirname(__FILE__) . '/test.mp4';

     list($error, $success_file_save_path) = $bot->downloadFile($file_id, 'test.mp4', $save_path);

    if($success_file_save_path) {
        echo 'File downloaded successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error;
    }


} catch (Exception $e) {
    die($e->getMessage());
}
