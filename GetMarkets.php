<?php
// ini_set('display_errors', 1);
include_once './inc/ccxt/ccxt.php';
include_once './inc/func.php';
$exchangeName = "binance";
$apiKey = '';
$secretKey = '';
$futures = false;

$exchangeClass = '\\ccxt\\' . $exchangeName;
$exchange = new $exchangeClass(array(
    'apiKey' => $apiKey,
    'secret' => $secretKey,
    'timeout' => 10000,
));
$exchange->enableRateLimit = true;
if ($futures){$exchange->options['defaultType'] = 'future';}
$markets = $exchange->fetch_markets();
$markets_file = json_encode($exchange->fetch_markets());
file_put_contents("markets_$exchangeName.json", $markets_file);

foreach($markets as $market){
  echo "Market ID: " . $market['id'] . " | Symbol: " . $market['symbol'] . PHP_EOL;
}

?>
