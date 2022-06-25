<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\ClientService;
use \Front\Service\ApiService;

class ClientController extends Controller {
    public function __construct() {
        $this->service = new ClientService();
    }

    public function createClient(Array $bodyContent) {
        try {
            $this->service->createClient($bodyContent);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function updateClient(Array $bodyContent, Int $clientId) {
        try {
            $this->service->update($bodyContent, $clientId);
            $this->sendSuccessMessage(getSuccessMessage('dataUpdate'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function removeClient(Int $clientId) {
        try {
            $this->service->remove($clientId);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function getClient($clientId) {
        $client = [];

        try {
            $assoc = new \DataBase\ClientAssocMarkupAssocCompany();
            $apiService = new ApiService();
            $assocPCDb = new \DataBase\ClientAssocPromoCode();

            $client['overall'] = $this->service->fetch($clientId);
            $client['assoc_markup_company'] = $assoc->fetchByClient($clientId);
            $client['apis'] = $apiService->fetchByClient($clientId);
            $client['promo_codes'] = $assocPCDb->fetchByClient($clientId);
            $this->sendContent($client);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function getClients() {
        try {
            $client = $this->service->fetchAll();
            $this->sendContent($client);
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}