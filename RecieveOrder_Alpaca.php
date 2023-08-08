<?php
// ini_set('display_errors', 1);
include_once './inc/func.php';
require './vendor/autoload.php';
use Alpaca\Alpaca;
$exchangeName = "alpaca";
$apiKey = 'PKK69DHP0BX7KRJC1GTG';
$secretKey = 'gXCDWqAMMvsyhhycsZlUlV2wH986obJoWjAxtVIA';
$retryAttempts = 5;
$retryDelay = 3; // in seconds

$alpaca = new Alpaca($apiKey, $secretKey);
//Block all IPs Except Tradingview
// $ip = ipCheck();
// $tv_ip_list = array("52.89.214.238","34.212.75.30","54.218.53.128","52.32.178.7");
// if (!in_array($ip, $tv_ip_list)) {die("Not Allowed");}
$checkdata = checkwebsite('alpaca', $apiKey, $secretKey);

$tvOrder = trim(file_get_contents('php://input'));
logger("Webhook Received (Alpaca) | $tvOrder");

if($tvOrder){
  $tvOrderData = parseWebhookData($tvOrder);

  $symbol = strtoupper($tvOrderData['symbol']) ?? null;
  $direction = $tvOrderData['direction'] ?? null;
  $orderSide = $tvOrderData['order'] ?? null;
  $orderType = $tvOrderData['type'] ?? null;
  $quantity = $tvOrderData['quantity'] ?? null;
  $limitPrice = $tvOrderData['limitprice'] ?? null;
  $limitPercentageOffset = $tvOrderData['limitpercentageoffset'] ?? null;
  $leverage = $tvOrderData['lx'] ?? null;
  $side = ($direction == 'long' && $orderSide == 'buy') || ($direction == 'short' && $orderSide == 'sell') ? 'buy' : 'sell';
  if($symbol !== null && $direction !== null && $orderSide !== null && $orderType !== null && $quantity !== null){
    $getAccountInfo = $alpaca->getAccount()->getResponse();
    $getAvailableBalance = $getAccountInfo->cash;
    $quantity = trim($quantity, '%');
    $quantity = $getAvailableBalance * ($quantity / 100);
    $time_in_force = 'day';
    $success = false;
    $attempt = 1;

    try{
      if ($orderType === 'limit') {
        if($limitPrice && empty($limitPercentageOffset)){
          logger("Placing Order (Alpaca) | Symbol: $symbol, Side:  $side, Type: $orderType, Qty: $quantity, Price: $limitPrice");
          while (!$success && $attempt <= $retryAttempts) {
              try {
                  $order = $alpaca->createOrder($symbol, $quantity, $side, $orderType, $time_in_force, $limitPrice)->getResponse();
                  $orderID = $order->id;
                  $status = $order->status;
                  logger("Order Placed (Alpaca) | OrderID: $orderID, Status: $status");
                  $success = true;
              } catch (Exception $e) {
                  logger("Error Placing Order (Alpaca) |". $e->getMessage());
                  sleep($retryDelay);
                  $attempt++;
              }
          }
        }else if(empty($limitPrice) && $limitPercentageOffset){
          $lastQuoteReq = $alpaca->getLastTrade($symbol)->getResponse();
          $lastPrice = $lastQuoteReq->trade->p;
          $offsetPercentage = trim($limitPercentageOffset, '%');
          if ($offsetPercentage !== '') {$offset = $last_price * ($offsetPercentage / 100);} else {$offset = 0;}
          $limitPrice = $last_price + $offset;
          logger("Placing Order (Alpaca) | Pair: $symbol, Side:  $side, Type: $orderType, Qty: $quantity, PercentageOffset: $limitPercentageOffset, Price: $limitPrice");
          while (!$success && $attempt <= $retryAttempts) {
              try {
                  $order = $alpaca->createOrder($symbol, $quantity, $side, $orderType, $time_in_force, $limitPrice)->getResponse();
                  $orderID = $order->id;
                  $status = $order->status;
                  logger("Order Placed (Alpaca) | OrderID: $orderID, Status: $status");
                  $success = true;
              } catch (Exception $e) {
                  logger("Error Placing Order (Alpaca) |". $e->getMessage());
                  sleep($retryDelay);
                  $attempt++;
              }
          }
        }
      } elseif ($orderType === 'market') {
          while (!$success && $attempt <= $retryAttempts) {
              try {
                  $order = $alpaca->createOrder($symbol, $quantity, $side, $orderType, $time_in_force)->getResponse();
                  $orderID = $order->id;
                  $status = $order->status;
                  logger("Order Placed (Alpaca) | OrderID: $orderID, Status: $status");
                  $success = true;
              } catch (Exception $e) {
                  logger("Error Placing Order (Alpaca) |". $e->getMessage());
                  sleep($retryDelay);
                  $attempt++;
              }
          }
      } else {
          throw new Exception("Invalid order type: $orderType");
      }
    } catch (Exception $e) {
        logger("Error Placing Order (Alpaca) |". $e->getMessage());
    }
  }
}else{
  echo 'Error';
}
?>
