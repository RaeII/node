<?php

namespace Front\Service;

use \Util\Validator;
use \Service\ClientAssocMarkupAssocCompanyOutService;

class ClientAssocMarkupAssocCompanyService extends ClientAssocMarkupAssocCompanyOutService {

    public function create(Array $ids) {
        $NEEDED_KEYS = array("companys_id", "clients_id", "markups_id");
        $dbCompany = new \DataBase\Company();
        $dbMarkup = new \DataBase\Markup();
        $dbClient = new \DataBase\Client();

        try {
            Validator::validateJSONKeys($ids, $NEEDED_KEYS);

            if(count($ids['companys_id']) != count($ids['clients_id'])
            || count($ids['companys_id']) != count($ids['markups_id'])) throw new \Exception(getErrorMessage('incorrectAssocParamNum'));
            
            $this->dataBase->startTransaction('mysql');
            for ($index=0; $index < count($ids['companys_id']); $index++) { 
                $companyId = $ids['companys_id'][$index];
                $clientId = $ids['clients_id'][$index];
                $markupId = $ids['markups_id'][$index];

                if(count($dbCompany->fetch($ids['companys_id'][$index])) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
                if(count($dbMarkup->fetch($ids['markups_id'][$index])) <= 0) throw new \Exception(getErrorMessage('markupNotFound'));
                if(count($dbClient->fetch($ids['clients_id'][$index])) <= 0) throw new \Exception(getErrorMessage('clientNotFound'));

                $this->dataBase->create($markupId, $companyId, $clientId);
            }
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove(Int $assocId) {
        try {
            $this->dataBase->delete($assocId);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
