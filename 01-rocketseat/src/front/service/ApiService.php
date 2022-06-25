<?php

namespace Front\Service;

use \Util\SecureData;
use \Util\Validator;
use \DataBase\ApiService as ApiServiceDataBase;
use \DataBase\ClientAssocApiService;

class ApiService {

    public function __construct() {
        $this->dataBase = new ApiServiceDataBase();
    }

    public function create(Array $bodyContent) {
        $NEEDEDOVERALLKEYS = ['lg_name', 'pwd', 'company_id', "clients_id"];
        $dbClient = new \DataBase\Client();
        $dbCompany = new \DataBase\Company();

        try {
            Validator::validateJSONKeys($bodyContent, $NEEDEDOVERALLKEYS);

            $bodyContent['lg_name'] = SecureData::secure($bodyContent['lg_name'], 'Login Name');
            $bodyContent['pwd'] = SecureData::secure($bodyContent['pwd'], 'Password');
            $bodyContent['company_id'] = SecureData::secure($bodyContent['company_id'], 'Companhia');
            $bodyContent['clients_id'] = SecureData::secure($bodyContent['clients_id']);

            if(count($dbCompany->fetch($bodyContent['company_id'])) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
            if(count($this->dataBase->fetchByColumn('login_name', $bodyContent['lg_name'])) > 0) throw new \Exception('Login name já cadastrado.');
            $bodyContent['pwd'] = SecureData::encryptData($bodyContent['pwd']);

            $apiAssoc = new ClientAssocApiService();
            $this->dataBase->startTransaction('mysql');
            $apiId = $this->dataBase->create($bodyContent);
            foreach ($bodyContent['clients_id'] as $clientId) {
                if(count($dbClient->fetch($clientId)) <= 0) throw new \Exception(getErrorMessage('clientNotFound'));

                $apiAssoc->create($apiId, $clientId);
            }
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function deleteApi(Int $apiId) {
    //     try {
    //         $apiId = SecureData::secure($apiId, 'Api');
            
    //         $apiAssoc = new ClientAssocApiService();
            
    //         $this->dataBase->startTransaction('mysql');
    //         $apiAssoc->deleteAssocByApi($apiId);
    //         $this->dataBase->deleteApi($apiId);
    //         $this->dataBase->commit('mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function update(Array $bodyContent, Int $apiId) {
        $NEEDEDOVERALLKEYS = ['lg_name', 'pwd', 'status', 'company_id'];

        try {
            Validator::validateJSONKeys($bodyContent, $NEEDEDOVERALLKEYS);

            $bodyContent['lg_name'] = SecureData::secure($bodyContent['lg_name'], 'Login Name');
            $bodyContent['pwd'] = SecureData::secure($bodyContent['pwd'], 'Password');
            // $bodyContent['token'] = SecureData::secure($bodyContent['token']);
            $bodyContent['status'] = SecureData::secure($bodyContent['status'], 'Status');
            $bodyContent['company_id'] = SecureData::secure($bodyContent['company_id'], 'Companhia');

            $bodyContent['pwd'] = SecureData::encryptData($bodyContent['pwd']);
            $this->dataBase->startTransaction('mysql');
            $this->dataBase->update($bodyContent, $apiId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByClient(Int $clientId) {
        try {
            $clientId = SecureData::secure($clientId);

            return $this->dataBase->fetchByClient($clientId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getApi($apiId) {
        try {
            return $this->dataBase->fetch($apiId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getApis() {
        try {
            return $this->dataBase->fetchAll();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}