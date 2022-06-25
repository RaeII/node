<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\CompanyService;

class CompanyController extends Controller {
    public function __construct() {
        $this->service = new CompanyService();
    }

    public function getCompany(Int $companyId) {
        try {
            $companys = $this->service->getCompany($companyId);
            $this->sendContent($companys);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getCompanys() {
        try {
            $companys = $this->service->getCompanys();
            $this->sendContent($companys);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}