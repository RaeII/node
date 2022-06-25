<?php

namespace Front\Service;

use \Util\SecureData;
use \DataBase\ClientAssocApiService;
use \DataBase\Client;
use \DataBase\ApiService;

class ApiServiceAssocClientService extends \Service\ApiServiceAssocClientOutService {

    public function addAssoc(Int $apiId, INt $clientId) {
        try {
            $apiId = SecureData::secure($apiId);
            $clientId = SecureData::secure($clientId);
            $clientDataBase = new Client();
            $apiServiceDataBase = new ApiService();

            if(count($clientDataBase->fetch($clientId)) <= 0) throw new \Exception(getErrorMessage('clientNotFound'));
            if(count($apiServiceDataBase->fetch($apiId)) <= 0) throw new \Exception(getErrorMessage('apiNotFound'));
            if(count($this->dataBase->selectApiAssocByAssoc($apiId, $clientId)) > 0) throw new \Exception(getErrorMessage('associationAlreadyExist'));

            $this->dataBase->startTransaction('mysql');
            $this->dataBase->create($apiId, $clientId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove(Int $assocId) {
        try {
            $assocId = SecureData::secure($assocId);

            $this->dataBase->startTransaction('mysql');
            $this->dataBase->delete($assocId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function deleteAssocByIds(Int $apiId, Int $clientId) {
        try {
            $apiId = SecureData::secure($apiId);
            $clientId = SecureData::secure($clientId);

            $this->dataBase->startTransaction('mysql');
            $this->dataBase->deleteAssocByIds($apiId, $clientId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}