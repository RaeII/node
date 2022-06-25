<?php

namespace Front\Controller;

use \Controller\Controller;
use \Service\ClientAssocPromoCodeService;

class ClientAssocPromoCodeController extends Controller {
    public function __construct() {
        $this->service = new ClientAssocPromoCodeService();
    }

    public function fetch(Int $id) {
        try {
            $promoCode = $this->service->fetch($id);
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
        try {
            $this->service->remove($id);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function removeByClient(Int $clientId) {
        try {
            $this->service->remove($clientId);
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

    //         // // $apiAssocClientService->deleteAssoc($assocId);
    //         // $apiAssocClientService->deleteAssocByIds($apiId, $clientId);
    //         // $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }
}