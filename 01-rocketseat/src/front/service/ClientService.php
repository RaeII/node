<?php

namespace Front\Service;

use \Util\SecureData;
use \Util\Validator;

class ClientService extends \Service\ClientOutService {
    private $NEEDEDOVERALLKEYS = ['company_name', 'trading_name', 'phone', 'apply_markup', 'apply_promo_code', 'apply_promo_code_repass', 'promo_code_value_repass', 'fare_equal_net', 'promo_codes_id', 'apis', 'markups_id', 'companys_id'];
    private $NEEDEDOVERALLPUTKEYS = ['company_name', 'trading_name', 'phone', 'apply_markup', 'apply_promo_code', 'apply_promo_code_repass', 'promo_code_value_repass', 'fare_equal_net'];

    public function createClient(Array $client) {
        try {
            $dbCompany = new \DataBase\Company();
            $dbMarkup = new \DataBase\Markup();

            Validator::validateJSONKeys($client, $this->NEEDEDOVERALLKEYS);
            Validator::phoneNumber($client['phone']);

            // Secure all data
            $client['company_name'] = SecureData::secure($client['company_name'], 'Nome da Companhia');
            $client['trading_name'] = SecureData::secure($client['trading_name']);
            $client['phone'] = SecureData::secure($client['phone']);
            $client['apply_markup'] = SecureData::secure($client['apply_markup'], 'Aplicar Markup');
            $client['apply_promo_code'] = SecureData::secure($client['apply_promo_code'], 'Aplicar Promo Code');
            $client['apply_promo_code_repass'] = SecureData::secure($client['apply_promo_code_repass'], 'Repassar valor no Promo Code');
            $client['promo_code_value_repass'] = SecureData::secure($client['promo_code_value_repass'], 'Valor de repasse do Promo Code');
            $client['fare_equal_net'] = SecureData::secure($client['fare_equal_net'], 'Valor igual a net.');
            $client['promo_codes_id'] = SecureData::secure($client['promo_codes_id']);
            $client['markups_id'] = SecureData::secure($client['markups_id']);
            $client['companys_id'] = SecureData::secure($client['companys_id']);
            $client['apis'] = SecureData::secure($client['apis']);

            if(count($client['companys_id']) != count($client['markups_id'])) throw new \Exception(getErrorMessage('incorrectAssocParamNum'));

            // Check if exist markups and companys provided.
            for ($index = 0; $index < count($client['markups_id']); $index++) {
                if(count($dbCompany->fetch($client['companys_id'][$index])) <= 0) throw new \Exception(getErrorMessage('companyNotFound'));
                if(count($dbMarkup->fetch($client['markups_id'][$index])) <= 0) throw new \Exception(getErrorMessage('markupNotFound'));
            }

            // Check if has client with same phone, company name or trading name.
            if(count($this->dataBase->fetchByColumn('company_name', $client['company_name'])) > 0) throw new \Exception("Nome da companhia já existente.");
            if(count($this->dataBase->fetchByColumn('trading_name', $client['trading_name'])) > 0) throw new \Exception("Nome Fantasia da companhia já existente.");
            if(count($this->dataBase->fetchByColumn('phone', $client['phone'])) > 0) throw new \Exception("Telefone já existente.");

            $assoc = new \DataBase\ClientAssocMarkupAssocCompany();
            $clientAssocPCDB = new \DataBase\ClientAssocPromoCode();
            $promoCodeDb = new \DataBase\PromoCode();

            $this->dataBase->startTransaction('mysql');
            $clientId = $this->dataBase->create($client);
            $this->dataBase->createAssocApis($client['apis'], $clientId);

            for ($index=0; $index < count($client['markups_id']); $index++) { 
                $markupId = $client['markups_id'][$index];
                $companyId = $client['companys_id'][$index];

                $assoc->create($markupId, $companyId, $clientId);
            }
            foreach ($client['promo_codes_id'] as $promoCodeId) {
                if(count($promoCodeDb->fetch($promoCodeId)) <= 0) throw new \Exception(getErrorMessage('promoCodeNotFound'));

                $clientAssocPCDB->create($promoCodeId, $clientId);
            }

            // $markupDataBase->commit('mysql');
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update(Array $client, Int $clientId) {

        try {
            Validator::validateJSONKeys($client, $this->NEEDEDOVERALLPUTKEYS);
            $client['company_name'] = SecureData::secure($client['company_name'], 'Nome da Companhia');
            $client['trading_name'] = SecureData::secure($client['trading_name']);
            $client['phone'] = SecureData::secure($client['phone']);
            $client['apply_markup'] = SecureData::secure($client['apply_markup']);
            $client['apply_promo_code'] = SecureData::secure($client['apply_promo_code']);
            $client['apply_promo_code_repass'] = SecureData::secure($client['apply_promo_code_repass']);
            $client['promo_code_value_repass'] = SecureData::secure($client['promo_code_value_repass']);
            $client['fare_equal_net'] = SecureData::secure($client['fare_equal_net']);
            $clientId = SecureData::secure($clientId);

            $this->dataBase->startTransaction('mysql');
            $this->dataBase->update($client, $clientId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove($clientId) {
        try {
            $assoc = new \DataBase\ClientAssocMarkupAssocCompany();
            $assocPCDB = new \DataBase\ClientAssocPromoCode();
            $clientId = SecureData::secure($clientId);
            
            $this->dataBase->startTransaction('mysql');
            $assoc->removeByClient($clientId);
            $assocPCDB->removeByClient($clientId);
            $this->dataBase->remove($clientId);
            $this->dataBase->commit('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
