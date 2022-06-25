<?php

namespace Service;

use \Util\Validator;
use \Util\SecureData;
use \Service\Service;
use \DataBase\ClientAssocPromoCode;

class ClientAssocPromoCodeService extends Service {

    public function __construct() {
        $this->dataBase = new ClientAssocPromoCode();
    }

    public function fetch($id) {
        return $this->dataBase->fetch($id);
    }

    public function fetchAll() {
        return $this->dataBase->fetchAll();
    }

    public function fetchEagerByPCId($id) {
        return $this->dataBase->fetchEagerByPCId($id);
    }

    public function fetchByClientAndCompany(Int $client, String $companyCode) {
        return $this->dataBase->fetchByClientAndCompany($client, $companyCode);
    }

    public function fetchByClientAndCredential(Int $client, String $credential, Array $idsToNegate = []) {
        return $this->dataBase->fetchByClientAndCredential($client, $credential, $idsToNegate);
    }

    public function create(Array $bodyContent) {
        $NEEDED_KEYS = ['promo_code_id', 'client_id'];
        $promoCodeDb = new \DataBase\PromoCode();
        $clientDb = new \DataBase\Client();
        
        Validator::validateJSONKeys($bodyContent, $NEEDED_KEYS);
        $bodyContent['promo_code_id'] = SecureData::secure($bodyContent['promo_code_id'], 'Promo Code');
        $bodyContent['client_id'] = SecureData::secure($bodyContent['client_id'], 'Cliente');

        if(count($clientDb->fetch($bodyContent['client_id'])) <= 0) throw new \Exception(getErrorMessage('clientNotFound'));
        if(count($promoCodeDb->fetch($bodyContent['promo_code_id'])) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));
        $toCheck = array('assoc.client_id' => $bodyContent['client_id'], 'assoc.promo_code_id' => $bodyContent['promo_code_id']);
        if(count($this->dataBase->fetchByFields($toCheck)) > 0) throw new \Exception(getErrorMessage('associationAlreadyExist'));
        $this->dataBase->create($bodyContent['promo_code_id'], $bodyContent['client_id']);
    }

    public function fetchByClient(Int $clientId) {
        $clientId = SecureData::secure($clientId, 'Cliente');
        $this->dataBase->fetchByClient($clientId);
    }

    public function remove(Int $assocId) {
        $assocId = SecureData::secure($assocId, 'Assoc');

        if(count($this->dataBase->fetch($assocId)) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));

        $this->dataBase->remove($assocId);
    }

    public function removeByClient(Int $clientId) {
        try {
            $id = SecureData::secure($clientId, 'Cliente');
            
            // if(count($this->dataBase->fetch($id)) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));
            $this->dataBase->removeByClient($id);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}