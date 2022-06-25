<?php

namespace Service;

use \DataBase\FareRule;

class FareRuleService {

    public function __construct() {
        $this->dataBase = new FareRule();
    }

    public function fetchAll() {
        try {
            return  $this->dataBase->fetchAll();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}