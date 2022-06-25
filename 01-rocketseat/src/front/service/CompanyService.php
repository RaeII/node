<?php

namespace Front\Service;

use \Util\SecureData;
use \Util\Validator;
use \DataBase\Company;

class CompanyService {

    public function __construct() {
        $this->dataBase = new Company();
    }

    public function getCompany($companyId) {
        try {
            return $this->dataBase->fetch($companyId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCompanys() {
        try {
            return $this->dataBase->fetchAll();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}