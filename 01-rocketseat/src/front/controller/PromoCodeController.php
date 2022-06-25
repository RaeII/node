<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\PromoCodeService;

class PromoCodeController extends Controller {
    public function __construct() {
        $this->service = new PromoCodeService();
    }

    public function fetch(Int $id) {
        $clientAsssocPCSvc = new \Service\ClientAssocPromoCodeService();
        $companySvc = new \Front\Service\CompanyService();

        try {
            $promoCode = $this->service->fetch($id);
            $clients = $clientAsssocPCSvc->fetchEagerByPCId($id);
            if(count($promoCode) > 0) {
                $company = $companySvc->getCompany($promoCode['company_id']);
                unset($promoCode['company_id']);
                $promoCode['company'] = $company;
                $promoCode['clients'] = $clients;
            }
            $this->sendContent($promoCode);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchAll() {
        try {
            $promoCodes = $this->service->fetchAll();
            $this->sendContent($promoCodes);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function create(Array $bodyContent) {
        try {
            $this->service->create($bodyContent);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function remove(Int $id) {
        $clientAsssocPCDB = new \DataBase\ClientAssocPromoCode();

        try {
            $clientAsssocPCDB->removeByPromoCode($id);
            $this->service->remove($id);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function update(Array $bodyContent, Int $apiId) {
        try {
            $this->service->update($bodyContent, $apiId);
            $this->sendSuccessMessage(getSuccessMessage('dataUpdate'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    // public function addAssoc(Int $apiId, Int $clientId) {
    //     try {
    //         $apiAssocClientService = new ApiServiceAssocClientService();
    //         $apiAssocClientService->addAssoc($apiId, $clientId);
    //         $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }

    // public function removeAssoc(Int $apiId, Int $clientId) {
    //     try {
    //         // $apiAssocClientService = new ApiServiceAssocClientService();

    //         // // $apiAssocClientService->delete($assocId);
    //         // $apiAssocClientService->deleteAssocByIds($apiId, $clientId);
    //         // $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }
}