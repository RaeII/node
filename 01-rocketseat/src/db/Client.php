<?php
namespace DataBase;

class Client extends DataBase {
    public function fetch($clientId) {
        $query = "SELECT 	client.id AS clientId, 
                            client.company_name AS companyName, 
                            client.trading_name AS tradingName, 
                            client.phone,
                            client.apply_markup AS applyMarkup,
                            client.apply_promo_code AS applyPromoCode,
                            client.apply_promo_code_repass AS applyPromoCodeRepass,
                            client.promo_code_value_repass AS promoCodeRepassValue,
                            client.fare_equal_net AS fareEqualNet,
                            client.show_credentials_info
                            FROM client
                        WHERE client.id = $clientId;";
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

    public function fetchAll() {
        $query = "SELECT 	client.id AS clientId, 
                            client.company_name AS companyName, 
                            client.trading_name AS tradingName, 
                            client.phone,
                            client.apply_markup AS applyMarkup,
                            client.apply_promo_code AS applyPromoCode,
                            client.apply_promo_code_repass AS applyPromoCodeRepass,
                            client.promo_code_value_repass AS promoCodeRepassValue,
                            client.fare_equal_net AS fareEqualNet,
                            client.show_credentials_info
                            FROM client";
        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByColumn($column, $condition) {
        $query = "SELECT 	client.id AS clientId, 
                            client.company_name AS companyName, 
                            client.trading_name AS tradingName, 
                            client.phone
                            FROM client
                            WHERE $column = '$condition'";

        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function create(Array $client) {
        $query = "INSERT INTO client (company_name, trading_name, phone, apply_markup, apply_promo_code, apply_promo_code_repass, promo_code_value_repass, fare_equal_net, alteration_date) 
                    VALUES (:company_name, :trading_name, :phone, :apply_markup, :apply_promo_code, :apply_promo_code_repass, :promo_code_value_repass, :fare_equal_net, :alteration_date);";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':company_name', $client['company_name']);
            $this->bindParam(':trading_name', $client['trading_name']);
            $this->bindParam(':phone', $client['phone']);
            $this->bindParam(':apply_markup', $client['apply_markup']);
            $this->bindParam(':apply_promo_code', $client['apply_promo_code']);
            $this->bindParam(':apply_promo_code_repass', $client['apply_promo_code_repass']);
            $this->bindParam(':promo_code_value_repass', $client['promo_code_value_repass']);
            $this->bindParam(':fare_equal_net', $client['fare_equal_net']);
            $this->bindParam(':alteration_date', date('Y/m/d H:i:s'));
            $this->insertPreparedQuery('mysql');

            return $this->lastInsertId('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function createAssocApis($assocs, $cliendId) {
        try {
            foreach ($assocs as $assoc) {
                $query = "INSERT INTO client_assoc_api_service_credential (client_id, api_service_credential_id, alteration_date) VALUES (:client_id, :api_service_credential_id, :alteration_date);";

                $this->setSqlManager($query, 'mysql');
                $this->bindParam(':client_id', $cliendId);
                $this->bindParam(':api_service_credential_id', $assoc);
                $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
                $this->insertPreparedQuery('mysql');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // ################################
    // public function insertDiscountOver($discountOver, $clientId) {
    //     $query = "INSERT INTO discount_over (fare_equal_net, promo_code, promo_code_value_repass, alteration_date, client_id) 
    //         VALUES (:fare_equal_net, :promo_code, :promo_code_value_repass, :alteration_date, :client_id);";
        
    //     try {
    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':fare_equal_net', $discountOver['fare_net']);
    //         $this->bindParam(':promo_code', $discountOver['promo_code']);
    //         $this->bindParam(':promo_code_value_repass', $discountOver['promo_code_repass']);
    //         $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
    //         $this->bindParam(':client_id', $clientId);
    //         $this->insertPreparedQuery('mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function update($client, $clientId) {
        $query = "UPDATE client SET company_name = :company_name, trading_name = :trading_name, phone = :phone, 
                            apply_markup= :apply_markup, apply_promo_code= :apply_promo_code, apply_promo_code_repass = :apply_promo_code_repass, 
                            promo_code_value_repass = :promo_code_value_repass, fare_equal_net = :fare_equal_net
                    WHERE id = :where_id;";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':company_name', $client['company_name']);
            $this->bindParam(':trading_name', $client['trading_name']);
            $this->bindParam(':phone', $client['phone']);
            $this->bindParam(':apply_markup', $client['apply_markup']);
            $this->bindParam(':apply_promo_code', $client['apply_promo_code']);
            $this->bindParam(':apply_promo_code_repass', $client['apply_promo_code_repass']);
            $this->bindParam(':promo_code_value_repass', $client['promo_code_value_repass']);
            $this->bindParam(':fare_equal_net', $client['fare_equal_net']);
            $this->bindParam(':where_id', $clientId);
            $this->_update($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function updateDiscountOver($discountOver, $clientId) {
    //     $query = "UPDATE discount_over SET fare_equal_net = :fare_equal_net, promo_code = :promo_code, 
    //                 promo_code_value_repass = :promo_code_value_repass, alteration_date = :alteration_date 
    //                 WHERE client_id = :where_id";
    //     try {
    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':fare_equal_net', $discountOver['fare_net']);
    //         $this->bindParam(':promo_code', $discountOver['promo_code']);
    //         $this->bindParam(':promo_code_value_repass', $discountOver['promo_code_repass']);
    //         $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
    //         $this->bindParam(':where_id', $clientId);
    //         $this->_update($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function remove($clientId) {
        $query = "DELETE FROM client WHERE id = :where_id";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':where_id', $clientId);
            $this->delete($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function deleteDiscountOverByClient($clientId) {
    //     $query = "DELETE FROM discount_over WHERE client_id = :where_id";

    //     try {
    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':where_id', $clientId);
    //         $this->delete($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }
}