<?php

namespace DataBase;

class PaymentForm extends DataBase {

    public function fetchByServiceCredential(Int $credentialId, String $method) {
        $query = "SELECT * FROM payment_forms WHERE api_service_credential_id = $credentialId AND method_type = '$method'";

        return $this->select($query, 'mysql');
    }
}