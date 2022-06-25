<?php

namespace Front\Service;

use \Util\SecureData;
use \Util\Validator;
use \DataBase\Markup;

class MarkupService {
    private $NEEDEDMARKUPKEYS = ['description', 'role', 'value_type', 'value', 'companys_id', 'clients_id'];

    public function __construct() {
        $this->dataBase = new Markup();
    }

    public function create($markupJSON) {
        try {
            Validator::validateJSONKeys($markupJSON['markup'], $this->NEEDEDMARKUPKEYS);
            $markupJSON['markup']['description'] = SecureData::secure($markupJSON['markup']['description']);
            $markupJSON['markup']['role'] = SecureData::secure($markupJSON['markup']['role']);
            $markupJSON['markup']['value_type'] = SecureData::secure($markupJSON['markup']['value_type']);
            $markupJSON['markup']['value'] = SecureData::secure($markupJSON['markup']['value']);
            if(count($markupJSON['markup']['companys_id']) != count($markupJSON['markup']['clients_id'])) throw new \Exception(getErrorMessage('incorrectAssocParamNum'));

            $assoc = new \DataBase\ClientAssocMarkupAssocCompany();
            $clientDb = new \DataBase\Client();
            $companyDb = new \DataBase\Company();

            $this->dataBase->startTransaction('mysql');
            $markupId = $this->dataBase->create($markupJSON['markup']);
            for ($index=0; $index < count($markupJSON['markup']['companys_id']); $index++) { 
                $companyId = $markupJSON['markup']['companys_id'][$index];
                $clientId = $markupJSON['markup']['clients_id'][$index];
                if(count($clientDb->fetch($clientId)) <= 0) throw new \Exception(getErrorMessage('clientNotFound'));
                if(count($companyDb->fetch($companyId)) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
                if(count($assoc->fetchByAssociations($markupId, $companyId, $clientId)) > 0)
                    continue;
                $assoc->create($markupId, $companyId, $clientId);
            }
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove($markupId) {
        try {
            // SecureData::validKeyOrError($markupJSON, 'client_id');
            $markupId = SecureData::secure($markupId);
            // $markupJSON['client_id'] = SecureData::secure($markupJSON['client_id']);

            $assoc = new \DataBase\ClientAssocMarkupAssocCompany();

            $this->dataBase->startTransaction('mysql');

            $assoc->removeByMarkup($markupId);
            $this->dataBase->remove($markupId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($markupJSON, $markupId) {
        try {
            // Validator::validKeyOrError($markupJSON, 'id');
            Validator::validKeyOrError($markupJSON, 'description');
            Validator::validKeyOrError($markupJSON, 'role');
            Validator::validKeyOrError($markupJSON, 'value_type');
            Validator::validKeyOrError($markupJSON, 'value');
    
            // $markupJSON['id'] = SecureData::secure($markupJSON['id']);
            $markupJSON['description'] = SecureData::secure($markupJSON['description']);
            $markupJSON['role'] = SecureData::secure($markupJSON['role']);
            $markupJSON['value_type'] = SecureData::secure($markupJSON['value_type']);
            $markupJSON['value'] = SecureData::secure($markupJSON['value']);

            $this->dataBase->startTransaction('mysql');
            $this->dataBase->update($markupJSON, $markupId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // Used to get markups from a single client on By ClientController.
    public function selectMarkupByClient($clientId) {
        try {
            $clientId = SecureData::secure($clientId);

            return $this->dataBase->selectMarkupByClient($clientId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchAll() {
        try {
            $dbClient = new \DataBase\Client();
            $dbCompany = new \DataBase\Company();
            // $assoc = new \DataBase\ClientAssocMarkupAssocCompany();
            $markups = $this->dataBase->fetchAll();
            foreach ($markups as &$markup) {
                $markup["companys"] = [];
                $markup["clients"] = [];

                if(count($markup['clients_id']) > 0) {
                    foreach ($markup['clients_id'] as $clientId) {
                        $clientRes = $dbClient->fetch($clientId);
                        $id = $clientRes["clientId"];
                        $companyName = $clientRes["companyName"];

                        $markup["clients"][] = array(
                            "id" => $id,
                            "companyName" => $companyName
                        ); 
                    }
                }
                
                if(count($markup['companys_id']) > 0) {                    
                    foreach ($markup['companys_id'] as $companyId) {
                        $companyRes = $dbCompany->fetch($companyId);
                            
                        $id = $companyRes["companyId"];
                        $companyName = $companyRes["company_name"];
                        $markup["companys"][] = array(
                            "id" => $id,
                            "companyName" => $companyName
                        ); 
                    }
                }
                unset($markup['clients_id']);
                unset($markup['companys_id']);
            }
            unset($markup);
            return $markups;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetch(Int $markupId) {
        try {

            $markupId = SecureData::secure($markupId);
            $markup = $this->dataBase->fetch($markupId);
            return $markup;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}