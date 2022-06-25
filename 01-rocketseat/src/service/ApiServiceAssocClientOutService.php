<?php

namespace Service;

use \Util\SecureData;
use \Service\Service;
use \DataBase\ClientAssocApiService;

class ApiServiceAssocClientOutService extends Service{

    public function __construct() {
        $this->dataBase = new ClientAssocApiService();
    }

    public function fetchByClient(Int $clientId, String $compCode) {
        return $this->dataBase->fetchByClient($clientId, $compCode);
    }

}