<?php

namespace DataBase;

class PromoCode extends DataBase {

    public function create(Array $datas) {
        $query = "INSERT INTO promo_code (promo_code, status, alteration_date, company_id)
                    VALUES (:promo_code, :status, :alteration_date, :company_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':promo_code', $datas['promo_code']);
        $this->bindParam(':status', 'A');
        $this->bindParam(':alteration_date', date('Y-m-d H:i:s'));
        $this->bindParam(':company_id', $datas['company_id']);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }

    public function fetch(Int $id) {
        $query = "SELECT * FROM promo_code WHERE id = $id";

        $res = $this->select($query, 'mysql');
        if(count($res) > 0) {
            return $res[0];
        }else{
            return $res;
        }
    }

    public function fetchAll() {
        $query = "SELECT * FROM promo_code";

        return $this->select($query, 'mysql');
    }

    public function updatePC(Array $promoCode, Int $id) {
        $query = "UPDATE promo_code SET {$this->_createBindParams($promoCode)} WHERE id = $id";
        return $this->_prepareSql($query, 'mysql')->_bindValues($promoCode)->updatePreparedQuery('mysql');
    }

    public function remove(Int $id) {
        $query = "DELETE FROM promo_code WHERE id = $id";

        return $this->delete($query, 'mysql');
    }

    public function removeByClient($clientId) {
        $query = "DELETE FROM promo_code WHERE client_id = $clientId";

        return $this->delete($query, 'mysql');
    }

    public function fetchAllResultArrangedByPromoCode() {
        $query = "SELECT JSON_OBJECTAGG(promo_code, id) AS promo_codes FROM apivoos.promo_code;";

        return json_decode($this->select($query, 'mysql')[0]['promo_codes'], true);
    }

    public function fetchAllResultArrangedById() {
        $query = "SELECT JSON_OBJECTAGG(id, promo_code) AS promo_codes FROM apivoos.promo_code;";

        return json_decode($this->select($query, 'mysql')[0]['promo_codes'], true);
    }

    public function isUnique(Array $datas) {
        $query = "SELECT id FROM promo_code WHERE promo_code = :promo_code";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':promo_code', $datas['promo_code']);

        return !(count($this->select($query, 'mysql')) > 0);
    }
}