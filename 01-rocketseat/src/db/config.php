<?php

global $db;
$db = array();
$dbConf = array();
$pdo;

// if(!DEV_MODE) {
$dbConf['mysql']['host'] = '67.205.134.14:4089';
$dbConf['mysql']['dbname'] = 'apivoos';

$dbConf['mysql']['username'] = 'apivoos_demo';
$dbConf['mysql']['password'] = 'M0nkey_615243';
// }else {
//     $dbConf['mysql']['host'] = 'localhost:3306';
//     $dbConf['mysql']['dbname'] = 'apivoos';
//     $dbConf['mysql']['username'] = 'root';
//     $dbConf['mysql']['password'] = '123321';
// }
try {
    $pdo = new PDO("mysql:host={$dbConf['mysql']['host']};dbname={$dbConf['mysql']['dbname']}", "{$dbConf['mysql']['username']}", "{$dbConf['mysql']['password']}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db['mysql'] = $pdo;
} catch (\Exception $e) {
    \Util\Response::sendErrorMessage(getErrorMessage('dataBaseConnectionFail'));
}