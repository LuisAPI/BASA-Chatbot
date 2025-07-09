<?php
// test-curl.php

$url = 'http://127.0.0.1:11434/api/generate';
$data = [
    'model' => 'tinyllama',
    'prompt' => 'hello',
    'stream' => false
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

var_dump($response, $error);
