<?php

namespace Front\Controller;

use \Controller\Controller;
use \Front\Service\MarkupService;
// use \Front\Service\MarkupAssocService;

class MarkupController extends Controller {
    
    public function __construct() {
        $this->service = new MarkupService();
    }

    public function addMarkup(Array $bodyContent) {
        try {
            $this->service->create($bodyContent);
            $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function removeMarkup(Int $markupId) {
        try {
            $this->service->remove($markupId);
            $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function update(Array $bodyContent, Int $markupId) {
        try {
            $this->service->update($bodyContent, $markupId);
            $this->sendSuccessMessage(getSuccessMessage('dataUpdate'));
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    // public function removeMarkupAssoc(Int $assocId) {
    //     try {
    //         $markupAssocService = new MarkupAssocService();
    //         $markupAssocService->deleteMarkupAssoc($assocId);
    //         $this->sendSuccessMessage(getSuccessMessage('dataDelete'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }

    // public function addMarkupAssoc(Int $markupId, Int $clientId) {
    //     try {
    //         $markupAssocService = new MarkupAssocService();
    //         $markupAssocService->addMarkupAssoc($markupId, $clientId);
    //         $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }

    // public function addAssocCompany(Int $markupId, Int $companyId) {
    //     try {
    //         $assocService = new MarkupAssocService();
    //         $assocService->addAssocCompany($markupId, $companyId);
    //         $this->sendSuccessMessage(getSuccessMessage('dataInsert'));
    //     } catch (\Exception $e) {
    //         $this->sendErrorMessage($e->getMessage());
    //     }
    // }

    public function fetchAll() {
        try {
            $this->sendContent($this->service->fetchAll());
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }

    public function fetch($markupId) {
        $markup = [];
        try {
            
            $markup['overall'] = $this->sendContent($this->service->fetch());
            $markup['clients'] = $this->sendContent($this->service->fetch());
        } catch (\Exception $e) {
            $this->sendErrorMessage($e->getMessage());
        }
    }
}