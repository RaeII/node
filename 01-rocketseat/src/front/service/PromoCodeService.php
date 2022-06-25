<?php

namespace Front\Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\PromoCodeOutService;

class PromoCodeService extends PromoCodeOutService {

    public function create(Array $bodyContent) {
        $NEEDEDOVERALLKEYS = ['promo_code', 'status'];
        $apiCredentialService = new \Front\Service\ApiService();
        $companyService = new \Front\Service\CompanyService();

        try {
            if(isset($bodyContent['company_id']) && isset($bodyContent['api_service_credential_id'])) {
                throw new \Exception(getErrorMessage('onlySingleRelation'));
            }

            Validator::validateJSONKeys($bodyContent, $NEEDEDOVERALLKEYS);
            $bodyContent['promo_code'] =                    SecureData::secure($bodyContent['promo_code'], 'Promo Code');
            $bodyContent['status'] =                        SecureData::secure($bodyContent['status'], 'Status');
            if(isset($bodyContent['company_id'])) {
                $bodyContent['company_id'] =                    SecureData::secure($bodyContent['company_id'], 'Companhia');
                if(count($companyService->getCompany($bodyContent['company_id'])) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
            }
            if(isset($bodyContent['api_service_credential_id'])) {
                $bodyContent['api_service_credential_id'] =     SecureData::secure($bodyContent['api_service_credential_id'], 'Api Credential');
                if(count($apiCredentialService->getApi($bodyContent['api_service_credential_id'])) <= 0) throw new \Exception(getErrorMessage('apiNotFound'));
            }
            if(!$this->dataBase->isUnique($bodyContent)) throw new \Exception(getErrorMessage('uniqueValue', 'Promo Code'));
            $this->dataBase->create($bodyContent, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove(Int $id) {
        try {
            $id = SecureData::secure($id, 'Api');
            
            if(count($this->dataBase->fetch($id)) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));
            $this->dataBase->remove($id);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update(Array $bodyContent, Int $id) {
        $apiCredentialService = new \Front\Service\ApiService();
        $companyService = new \Front\Service\CompanyService();

        $NEEDEDOVERALLKEYS = ['promo_code', 'status'];
        $toUpdate = [];

        try {
            if(isset($bodyContent['company_id']) && isset($bodyContent['api_service_credential_id'])) {
                throw new \Exception(getErrorMessage('onlySingleRelation'));
            }

            Validator::validateJSONKeys($bodyContent, $NEEDEDOVERALLKEYS);

            if(count($this->dataBase->fetch($id)) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));
            if(isset($bodyContent['promo_code']))                   $toUpdate['promo_code'] =                   SecureData::secure($bodyContent['promo_code']);
            if(isset($bodyContent['status']))                       $toUpdate['status'] =                       SecureData::secure($bodyContent['status']);
            
            if(isset($bodyContent['company_id'])) {
                $bodyContent['api_service_credential_id'] = 'NULL';
                if(isset($bodyContent['company_id'])) $toUpdate['company_id'] = SecureData::secure($bodyContent['company_id']);
                if(count($companyService->getCompany($bodyContent['company_id'])) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
            }
            if(isset($bodyContent['api_service_credential_id'])) {
                $bodyContent['company_id'] = 'NULL';
                if(isset($bodyContent['api_service_credential_id'])) $toUpdate['api_service_credential_id'] = SecureData::secure($bodyContent['api_service_credential_id']);
                if(count($apiCredentialService->getApi($bodyContent['api_service_credential_id'])) <= 0) throw new \Exception(getErrorMessage('apiNotFound'));
            }

            // TO FIX
            // if(!$this->dataBase->isUnique($bodyContent)) throw new \Exception(getErrorMessage('uniqueValue', 'Promo Code'));
            $this->dataBase->updatePC($toUpdate, $id);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}