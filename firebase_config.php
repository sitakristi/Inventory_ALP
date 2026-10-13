<?php
require __DIR__.'/vendor/autoload.php';
use Kreait\Firebase\Factory;

$factory = (new Factory())
    ->withServiceAccount(__DIR__ . '/firebase_credentials.json')
    ->withDatabaseUri('https://inventory-5180a-default-rtdb.asia-southeast1.firebasedatabase.app/'); 

$auth = $factory->createAuth();
$database = $factory->createDatabase();
?>