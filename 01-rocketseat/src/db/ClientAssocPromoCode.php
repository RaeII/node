<?php

namespace DataBase;

class ClientAssocPromoCode extends DataBase {
    public function create($promoCId, $clientId) {
        $query = "INSERT INTO client_assoc_promo_code (client_id, promo_code_id) VALUES (:client_id, :promo_code_id);";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':client_id', $clientId);
        $this->bindParam(':promo_code_id', $promoCId);
        $this->insertPreparedQuery('mysql');
    }

    public function remove($assocId) {
        $query = "DELETE FROM client_assoc_promo_code WHERE id = :where_id";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':where_id', $assocId);
        $this->delete($query, 'mysql');
    }

    public function removeByClient($clientId) {
        $query = "DELETE FROM client_assoc_promo_code WHERE client_id = :clientId";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':clientId', $clientId);
        $this->delete($query, 'mysql');
    }

    public function removeByPromoCode($promoCodeId) {
        $query = "DELETE FROM client_assoc_promo_code WHERE promo_code_id = :promoCodeId";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':promoCodeId', $promoCodeId);
        $this->delete($query, 'mysql');
    }

    // public function removeAssocByPromoCode($promoCId) {
    //     $query = "DELETE FROM client_assoc_promo_code WHERE promo_code_id = :where_id";

    //     $this->setSqlManager($query, 'mysql');
    //     $this->bindParam(':where_id', $promoCId);
    //     $this->delete($query, 'mysql');
    // }

    public function fetch(Int $id) {
        $query = "SELECT * FROM client_assoc_promo_code WHERE id = $id";

        $res = $this->select($query, 'mysql');
        if(count($res) > 0) {
            return $res[0];
        }else {
            return $res;
        }
    }

    public function fetchAll() {
        $query = "SELECT * FROM client_assoc_promo_code";

        return $this->select($query, 'mysql');
    }

    public function fetchByClient(Int $clientId) {
        $query = "SELECT    promo_code.id AS promo_code_id, 
                            promo_code.promo_code AS promo_code, 
                            promo_code.status,
                            promo_code.company_id
                        FROM client_assoc_promo_code AS assoc 
                            INNER JOIN promo_code ON promo_code.id = assoc.promo_code_id 
                            INNER JOIN client ON client.id = assoc.client_id 
                        WHERE assoc.client_id = $clientId AND promo_code.status = 'A';";

        return $this->select($query, 'mysql');
    }

    public function fetchByClientAndCompany(Int $client, String $companyCode) {
        $query = "SELECT    promo_code.id AS promo_code_id, 
                            promo_code.promo_code AS promo_code, 
                            promo_code.status,
                            promo_code.company_id
                        FROM client_assoc_promo_code AS assoc 
                            INNER JOIN promo_code ON promo_code.id = assoc.promo_code_id 
                            INNER JOIN client ON client.id = assoc.client_id 
                            INNER JOIN company ON company.code = '$companyCode'
                        WHERE assoc.client_id = $client AND promo_code.company_id = company.id AND promo_code.status = 'A';";

        return $this->select($query, 'mysql');
    }

    public function fetchByClientAndCredential(Int $client, Int $credential, Array $idsToNegate = []) {
        $extraWhereParams = '';

        if(count($idsToNegate) > 0) $extraWhereParams = ' AND promo_code.id != ' . implode(' AND promo_code.id != ', $idsToNegate);

        $query = "SELECT    promo_code.id AS promo_code_id, 
                            promo_code.promo_code AS promo_code, 
                            promo_code.status,
                            promo_code.company_id
                        FROM client_assoc_promo_code AS assoc 
                            INNER JOIN promo_code ON promo_code.id = assoc.promo_code_id 
                            INNER JOIN client ON client.id = assoc.client_id 
                        WHERE assoc.client_id = $client AND promo_code.api_service_credential_id = $credential AND promo_code.status = 'A' $extraWhereParams;";

        return $this->select($query, 'mysql');
    }

    public function fetchByFields(Array $fields) {
        $query = "SELECT    promo_code.id AS promo_code_id, 
                            promo_code.promo_code AS promo_code, 
                            promo_code.status
                        FROM client_assoc_promo_code AS assoc 
                            INNER JOIN promo_code ON promo_code.id = assoc.promo_code_id 
                            INNER JOIN client ON client.id = assoc.client_id 
                            WHERE {$this->_createBindParamsWAND($fields)}";

        return $this->_prepareSql($query, 'mysql')->_bindValues($fields)->_select('mysql');
    }

    public function fetchEagerByPCId(Int $id) {
        $query = "SELECT    client.id AS client_id,
                            client.company_name AS company_name,
                            client.apply_promo_code,
                            client.apply_markup,
                            client.apply_promo_code_repass,
                            client.fare_equal_net,
                            client.promo_code_value_repass
                        FROM client_assoc_promo_code AS assoc 
                            INNER JOIN client ON client.id = assoc.client_id 
                        WHERE assoc.promo_code_id = $id;";

        $res = $this->select($query, 'mysql');

        if(count($res) > 0) {
            return $res[0];
        } else {
            return $res;
        }
    }
    // public function deleteMarkupAssocByClient($clientId) {
    //     try {
    //         $query = "DELETE FROM client_assoc_markup WHERE client_id = :where_id";

    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':where_id', $clientId);
    //         $this->delete($query, 'mysql');
    //     } catch (\Eception $e) {
    //         throw $e;
    //     }
    // }
}