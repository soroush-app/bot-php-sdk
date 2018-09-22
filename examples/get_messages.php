<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $messages = $bot->getMessages();

    foreach ($messages as $message) {
        $data = $message->getData();
        echo "New Message Received !" . PHP_EOL . "From: " . $data['from'] . PHP_EOL . "Type: " . $data['type'] . PHP_EOL;
        if ($data['type'] == 'TEXT') {
            echo "Body: " . $data['body'] . PHP_EOL;
        } elseif ($data['type'] == 'FILE') {
            echo "FileName: " . $data['fileName'] . PHP_EOL . "FileType: " . $data['fileType'] . PHP_EOL . "FileSize: " . $data['fileSize'] . PHP_EOL;
            // $save_file_path = './downloads/' . . time() . '_' . $data['fileName'];
            //$bot->downloadFile($data['fileUrl'], $data['fileName'], $save_file_path));
        } else {
            //etc ...
        }
        var_dump($data);
    }

} catch (Exception $e) {
    die($e->getMessage());
}
