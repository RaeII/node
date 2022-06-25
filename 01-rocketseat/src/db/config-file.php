<?php

global $db;
$db = array();
$dbConf = array();
$pdo;

$dbConf['mysql']['host'] = '';
$dbConf['mysql']['dbname'] = '';
$dbConf['mysql']['username'] = '';
$dbConf['mysql']['password'] = '';

try {
    $pdo = new PDO("mysql:host={$dbConf['mysql']['host']};dbname={$dbConf['mysql']['dbname']}", "{$dbConf['mysql']['username']}", "{$dbConf['mysql']['password']}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db['mysql'] = $pdo;
} catch (Exception $e) {
    \Util\Response::sendErrorMessage(getErrorMessage('dataBaseConnectionFail'));
}