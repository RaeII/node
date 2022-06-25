<?php

namespace Service;

use \DataBase\PaymentForm;

class PaymentFormService {

    public function __construct() {
        $this->dataBase = new PaymentForm();
    }

    public function fetchByServiceCredential(Int $credentialId, String $method) {
        try {
            return $this->dataBase->fetchByServiceCredential($credentialId, $method);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}