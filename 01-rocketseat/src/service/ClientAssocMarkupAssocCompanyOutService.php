<?php

namespace Service;

use \Service\Service;
use \DataBase\ClientAssocMarkupAssocCompany;

class ClientAssocMarkupAssocCompanyOutService extends Service {

    public function __construct() {
        $this->dataBase = new ClientAssocMarkupAssocCompany();
    }

    public function fetchByAssociations($markupId, $companyId, $clientId) {
        return $this->dataBase->fetchByAssociations($markupId, $companyId, $clientId);
    }

    public function fetchByClient($clientId) {
        return $this->dataBase->fetchByClient($clientId);
    }

    public function fetchSumByClient($clientId) {
        $res = $this->dataBase->fetchSumByClient($clientId);

        return count($res) > 0 ? $res[0] : $res;
    }

    public function fetchByClientAndRole($clientId, $role) {
        $res = $this->dataBase->fetchByClientAndRole($clientId, $role);

        return count($res) > 0 ? $res[0] : $res;
    }

    public function fetchByMarkup($markupId) {
        return $this->dataBase->fetchByMarkup($markupId);
    }

    public function fetchMarkupByClientAndCode($clientId, $aerialCompCode) {
        return $this->dataBase->fetchMarkupByClientAndCode($clientId, $aerialCompCode);
    }
}