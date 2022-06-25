<?php

namespace Controller;

use \Util\Response;

abstract class Controller {
    public static $JWT;

    public function sendContent($content) {
        Response::sendContent($content);
    }

    public function sendSuccessMessage($message) {
        Response::sendSuccessMessage($message);
    }

    public function sendErrorMessage($message) {
        Response::sendErrorMessage($message);
    }

    public function sendWarningMessage($message) {
        Response::sendWarningMessage($message);
    }
}