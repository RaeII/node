<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\ApiService;
use \Front\Service\ApiServiceAssocClientService;

class ApiController extends Controller {
    public function __construct() {
        $this->service = new ApiService();
    }

    public function getApi(Int $api) {
        try {
            $api = $this->service->getApi($api);
            $this->sendContent($api);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getApis() {
        try {
            $apis = $this->service->getApis();
            $this->sendContent($apis);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createApi(Array $bodyContent) {
        try {
            $this->service->create($bodyContent);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    // public function removeApi(Int $apiId) {
    //     try {
    //         $this->service->deleteApi($apiId);
    //         $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
    //     } catch (\Exception $e) {
    //         $this->sendErroMessage($e->getMessage());
    //     }
    // }

    public function update(Array $bodyContent, Int $apiId) {
        try {
            $this->service->update($bodyContent, $apiId);
            $this->sendSuccessMessage(getSuccessMessage('dataUpdate'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function addAssoc(Int $apiId, Int $clientId) {
        try {
            $apiAssocClientService = new ApiServiceAssocClientService();
            $apiAssocClientService->addAssoc($apiId, $clientId);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function removeAssoc(Int $apiId, Int $clientId) {
        try {
            $apiAssocClientService = new ApiServiceAssocClientService();

            // $apiAssocClientService->delete($assocId);
            $apiAssocClientService->deleteAssocByIds($apiId, $clientId);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}