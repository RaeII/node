<?php

namespace DataBase;

class UserAccount extends DataBase {
    public function fetch($userName, $pwd) {
        $result = [];

        try {
            $query = "SELECT    user_account.name,
                                user_account.id AS user_id,
                                user_account.email AS email,
                                user_account.name AS name,
                                user_account.last_name AS last_name,
                                user_account.work_phone AS work_phone,
                                user_account.other_phone AS other_phone,
                                user_account.account_level AS permission_level,
                                client.id AS clientId
                        FROM user_account
                        INNER JOIN client ON client.id = user_account.client_id
                        WHERE login_name = :login_name AND password = :pwd";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':login_name', $userName);
            $this->bindParam(':pwd', md5($pwd));
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchById($id) {
        $response = [];

        try {
            $query = "SELECT    user_account.name,
                                user_account.id AS user_id,
                                user_account.email AS email,
                                user_account.name AS name,
                                user_account.last_name AS last_name,
                                user_account.work_phone AS work_phone,
                                user_account.other_phone AS other_phone,
                                user_account.account_level AS permission_level,
                                client.id AS clientId
                        FROM user_account
                        INNER JOIN client ON client.id = user_account.client_id
                        WHERE user_account.id = :id";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':id', $id);

            $response = $this->select($query, 'mysql');

            if(count($response) > 0) {
                return $response[0];
            }else {
                return $response;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
?>