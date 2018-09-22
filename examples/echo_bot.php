<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $messages = $bot->getMessages();

    foreach ($messages as $message) {
        $data = $message->getData();
        echo 'New message received !' . PHP_EOL . 'From : ' . $data['from'] . ', Body : ' . $data['body'] . PHP_EOL;
        $data['to'] = $data['from'];
        unset($data['from']);
        list($error, $success) = $bot->sendRAW($data);

        if($success) {
            echo 'Message reply sent successfully' . PHP_EOL;
        } else {
            echo 'Fail : ' . $error. PHP_EOL;
        }
    }

} catch (Exception $e) {
    die($e->getMessage());
}
