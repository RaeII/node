<?php

session_start();
//**** CORS ****//
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: *");
header('Content-Type: application/json');
//**** CORS ****//

require_once 'src/vendor/autoload.php';
require_once 'src/config/responseMessage.php';
require_once 'src/config/environment.php';
require_once 'src/db/config.php';
// require 'test/files/fileGatter.php';
require_once 'src/routes/router.php';

