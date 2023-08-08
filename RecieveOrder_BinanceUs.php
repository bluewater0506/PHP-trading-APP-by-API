<?php
// ini_set('display_errors', 1);
include_once './inc/ccxt/ccxt.php';
include_once './inc/func.php';
$exchangeName = "binanceus";
$apiKey = '';
$secretKey = '';
$futures = false;
$retryAttempts = 5;
$retryDelay = 3; // in seconds

$exchangeClass = '\\ccxt\\' . $exchangeName;
$exchange = new $exchangeClass(array(
    'apiKey' => $apiKey,
    'secret' => $secretKey,
    'timeout' => 10000,
));
$exchange->enableRateLimit = true;
if ($futures){$exchange->options['defaultType'] = 'future';}
$checkdata = checkwebsite('BinanceUS ', $apiKey, $secretKey);

// // Save Markets to File
// $markets = json_encode($exchange->fetch_markets());
// file_put_contents("markets_$exchangeName.json", $markets);
// die();

//Block all IPs Except Tradingview
// $ip = ipCheck();
// $tv_ip_list = array("52.89.214.238","34.212.75.30","54.218.53.128","52.32.178.7");
// if (!in_array($ip, $tv_ip_list)) {die("Not Allowed");}


$tvOrder = trim(file_get_contents('php://input'));
logger("Webhook Received (BinanceUS) | $tvOrder");

if($tvOrder){
  $tvOrderData = parseWebhookData($tvOrder);
  $symbol = $tvOrderData['symbol'] ?? null;
  $direction = $tvOrderData['direction'] ?? null;
  $orderSide = $tvOrderData['order'] ?? null;
  $orderType = $tvOrderData['type'] ?? null;
  $quantity = $tvOrderData['quantity'] ?? null;
  $limitPrice = $tvOrderData['limitprice'] ?? null;
  $limitPercentageOffset = $tvOrderData['limitpercentageoffset'] ?? null;
  $leverage = $tvOrderData['lx'] ?? null;
  $side = ($direction == 'long' && $orderSide == 'buy') || ($direction == 'short' && $orderSide == 'sell') ? 'buy' : 'sell';
  $marketJsonData = file_get_contents("markets_$exchangeName.json");
  $marketData = json_decode($marketJsonData, true);
  $coin = null;
  $success = false;
  $attempt = 1;
  foreach ($marketData as $item) {
      if (strtolower($item['id']) === $symbol) {
          $coin = $item;
          break;
      }
  }
  if($symbol !== null && $direction !== null && $orderSide !== null && $orderType !== null && $quantity !== null && $coin !== null){
    if($side === 'buy'){
    $baseCoin = $coin['quoteId'];
    $balanceReq = $exchange->fetch_balance();
    $baseBalance = $balanceReq['free']["$baseCoin"];
    $quantity = trim($quantity, '%');
    $quantity = $baseBalance * ($quantity / 100);
    $ticker = $exchange->fetch_ticker($coin['id']);
    $lastPrice = $ticker['last'];
    $quantity = round(($quantity / $lastPrice),(int)$coin['info']['baseAssetPrecision']);
  }else if($side === 'sell'){
    $baseCoin = $coin['baseId'];
    $balanceReq = $exchange->fetch_balance();
    $baseBalance = $balanceReq['free']["$baseCoin"];
    $quantity = trim($quantity, '%');
    $quantity = $baseBalance * ($quantity / 100);
    $ticker = $exchange->fetch_ticker($coin['id']);
    $lastPrice = $ticker['last'];
  }
    try{
      if ($orderType === 'limit') {
        if($limitPrice && empty($limitPercentageOffset)){
          logger("Placing Order (BinanceUS) | Pair: $symbol, Side:  $side, Type: $orderType, Qty: $quantity, Price: $limitPrice");
          while (!$success && $attempt <= $retryAttempts) {
              try {
                  $order = $exchange->create_limit_order($coin['id'], $side, $quantity, $limitPrice);
                  $orderID = $order['id'];
                  $status = json_encode($order);
                  logger("Limit Order Placed (BinanceUS) | OrderID: $orderID, Status: $status");
                  $success = true;
              } catch (Exception $e) {
                  logger("Error Placing Order (BinanceUS) |". $e->getMessage());
                  sleep($retryDelay);
                  $attempt++;
              }
          }
        }else if(empty($limitPrice) && $limitPercentageOffset){
          $offsetPercentage = trim($limitPercentageOffset, '%');
          if ($offsetPercentage !== '') {$offset = $lastPrice * ($offsetPercentage / 100);} else {$offset = 0;}
          $limitPrice = $lastPrice + $offset;
          logger("Placing Order (BinanceUS) | Pair: $symbol, Side:  $side, Type: $orderType, Qty: $quantity, PercentageOffset: $limitPercentageOffset, Price: $limitPrice");
          while (!$success && $attempt <= $retryAttempts) {
              try {
                  $order = $exchange->create_limit_order($coin['id'], $side, $quantity, $limitPrice);
                  $orderID = $order['id'];
                  $status = json_encode($order);
                  logger("Limit Order Placed (BinanceUS) | OrderID: $orderID, Status: $status");
                  $success = true;
              } catch (Exception $e) {
                  logger("Error Placing Order (BinanceUS) |". $e->getMessage());
                  sleep($retryDelay);
                  $attempt++;
              }
          }
        }
      } elseif ($orderType === 'market') {
        while (!$success && $attempt <= $retryAttempts) {
            try {
                $order = $exchange->create_market_order($coin['id'], $side, $quantity);
                $orderID = $order['id'];
                $status = json_encode($order);
                logger("Market Order Placed (BinanceUS) | OrderID: $orderID, Status: $status");
                $success = true;
            } catch (Exception $e) {
                logger("Error Placing Order (BinanceUS) |". $e->getMessage());
                sleep($retryDelay);
                $attempt++;
            }
        }
      } else {
          throw new Exception("Invalid order type: $orderType");
      }
    } catch (Exception $e) {
        logger("Error Placing Order (BinanceUS) |". $e->getMessage());
    }
  }else{
    logger("Error | Missing Arguments");
  }
}else{
  echo 'Error';
}
?>
