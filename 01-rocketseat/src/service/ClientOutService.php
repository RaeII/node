<?php

namespace Service;

use \Util\SecureData;
use \Service\Service;
use \DataBase\Client;

class ClientOutService extends Service {
    public function __construct() {
        $this->dataBase = new Client();
    }

    public function fetchAll() {
        try {
            return $this->dataBase->fetchAll();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetch($clientId) {
        try {
            $clientId = SecureData::secure($clientId);

            return $this->dataBase->fetch($clientId);
        } catch (\Exception $e) {
            throw $e;
        }
    }


}
