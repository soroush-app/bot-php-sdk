<?php

require dirname(__FILE__) . '/../vendor/autoload.php';

$bot_token = 'your bot token';

$bot = new Soroush\Client($bot_token);

try {

    $to = 'bot user id';

    // simple type - 1 button
    $keyboard_data_1 = $bot->makeKeyboardData('Back');
    // simple type - 2 button
    $keyboard_data_2 = $bot->makeKeyboardData('Button 1|Button 2');
    // simple type - 2 rows
    $keyboard_data_3 = $bot->makeKeyboardData('row 1 - button 1|row 1 - button 2' . PHP_EOL . 'row 2 - button 1');
    // use array
    $keyboard_data_alt_1 = $bot->makeKeyboardData([['row 1 - button 1'], ['row 2 - button 1', 'row 2 - button 2']]);
    // use array - user text index
    $keyboard_data_alt_2 = $bot->makeKeyboardData([[['text' => 'row 1 - button 1']], [['text' => 'row 2 - button 1']], [['text' => 'row 3 - button 1'], ['text' => 'row 3 - button 2'],]]);
    // use array - use text and command
    $keyboard_data_alt_3 = $bot->makeKeyboardData([[['text' => 'row 1 - button 1', 'command' => 'help']], [['text' => 'row 2 - button 1', 'command' => 'back']], [['text' => 'row 3 - button 1', 'command' => 'nextpage'], ['text' => 'row 3 - button 2', 'command' => 'prevpage']]]);
    // use array - use index 0,1 (text,command)
    $keyboard_data_alt_4 = $bot->makeKeyboardData([[['row 1 - button 1', 'help']], [['row 2 - button 1', 'back']], [['row 3 - button 1', 'nextpage'], ['row 3 - button 2', 'prevpage']]]);

    list($error, $success) = $bot->sendText($to, 'Sample text with keyboard', $keyboard_data_1);

    if ($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error . PHP_EOL;
    }

} catch (Exception $e) {
    die($e->getMessage());
}
