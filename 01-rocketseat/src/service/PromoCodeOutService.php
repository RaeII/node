<?php

namespace Service;

use \DataBase\PromoCode;

class PromoCodeOutService {

    public function __construct() {
        $this->dataBase = new PromoCode();
    }

    
    // public function fetchByClient(Int $clientId) {
    //     try {
    //         $clientId = SecureData::secure($clientId);

    //         return $this->dataBase->fetchByClient($clientId);
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function fetch($apiId) {
        try {
            return $this->dataBase->fetch($apiId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchAll() {
        try {
            return $this->dataBase->fetchAll();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchAllResultArrangedByPromoCode() {
        try {
            return $this->dataBase->fetchAllResultArrangedByPromoCode();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}