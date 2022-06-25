<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\ClientAssocMarkupAssocCompanyService;

class ClientAssocMarkupAssocCompanyController extends Controller {
    public function __construct() {
        $this->service = new ClientAssocMarkupAssocCompanyService();
    }

    public function create(Array $bodyContent) {
        try {
            $this->service->create($bodyContent);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function remove(Int $assocId) {
        try {
            $this->service->delete($assocId);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}