
# Soroush Messenger Bot PHP SDK
Soroush Messenger Bot Wrapper for PHP

## Dependencies ##
- PHP 5.6+
- JSON Extension (php-json)
- cURL Extension (php-curl)

## Installation ##
Run the below commands
```bash
git clone https://github.com/soroush-app/bot-php-sdk
cd bot-php-sdk
composer install
```

## Usage ##

```php
require dirname(__FILE__) . '/vendor/autoload.php';

$bot_token = 'your-bot-token';
$bot = new Soroush\Client($bot_token);
try {
    $to = 'bot user id';
    list($error, $success) = $bot->sendText($to, 'Sample text');
    if($success) {
        echo 'Message sent successfully' . PHP_EOL;
    } else {
        echo 'Fail : ' . $error. PHP_EOL;
    }
} catch (Exception $e) {
    die($e->getMessage());
}

```
More examples are in the [examples](https://github.com/soroush-app/bot-php-sdk/tree/master/examples) folder.

 ## Contribute ##
 Contributions to the package are always welcome!
 - Report any idea, bugs or issues you find on the [issue tracker](https://github.com/soroush-app/bot-php-sdk/issues).
 - You can grab the source code at the package's [Git repository](https://github.com/soroush-app/bot-php-sdk.git).