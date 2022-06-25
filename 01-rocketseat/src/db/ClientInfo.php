<?php
namespace DataBase;

class ClientInfo extends DataBase {
    public function fetch($id) {
        $query = "SELECT * FROM client_info
                        WHERE id = $id;";
        try {
            $response = $this->select($query, 'mysql');
            if(count($response) > 0) 
                return $response[0];
            else 
                return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function create(Array $client) {
    //     $query = "INSERT INTO client (company_name, trading_name, phone, apply_markup, apply_promo_code, apply_promo_code_repass, promo_code_value_repass, fare_equal_net, alteration_date) 
    //                 VALUES (:company_name, :trading_name, :phone, :apply_markup, :apply_promo_code, :apply_promo_code_repass, :promo_code_value_repass, :fare_equal_net, :alteration_date);";

    //     try {
    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':company_name', $client['company_name']);
    //         $this->bindParam(':trading_name', $client['trading_name']);
    //         $this->bindParam(':phone', $client['phone']);
    //         $this->bindParam(':apply_markup', $client['apply_markup']);
    //         $this->bindParam(':apply_promo_code', $client['apply_promo_code']);
    //         $this->bindParam(':apply_promo_code_repass', $client['apply_promo_code_repass']);
    //         $this->bindParam(':promo_code_value_repass', $client['promo_code_value_repass']);
    //         $this->bindParam(':fare_equal_net', $client['fare_equal_net']);
    //         $this->bindParam(':alteration_date', date('Y/m/d H:i:s'));
    //         $this->insertPreparedQuery('mysql');

    //         return $this->lastInsertId('mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }
}