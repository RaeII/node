<?php

namespace Service;

use \Util\Validator;
use Util\SecureData;
use \Service\Service;
use \DataBase\AerialStations;

class AerialStationsService extends Service {

    public function __construct() {
        $this->dataBase = new AerialStations();
    }

    public function fetch() {
        return $this->dataBase->fetch();
    }

    public function fetchLike(String $code) {
        Validator::existValueOrError($code, "Codigo Iata");

        $code = SecureData::secure($code);
        return $this->dataBase->fetchLike(str_replace('--', ' ', $code));
    }
}