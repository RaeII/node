<?php

namespace DataBase;

class ApiService extends DataBase {
    public function fetch($apiServiceId) {
        $query = "SELECT    api_service_credential.id AS apiServiceId,
                            api_service_credential.login_name AS loginName
                            FROM api_service_credential
                            WHERE api_service_credential.id = $apiServiceId;";

        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchAll() {
        $query = "SELECT    api_service_credential.id AS apiServiceId,
                            api_service_credential.login_name AS loginName
                            FROM api_service_credential;";

        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByClient($clientId) {
        $query = "SELECT    api_service_credential.id AS apiServiceId,
                            api_service_credential.login_name AS loginName,
                            company.company_name AS companyName
                            FROM client_assoc_api_service_credential
                            INNER JOIN api_service_credential ON api_service_credential.id = client_assoc_api_service_credential.api_service_credential_id
                            INNER JOIN company ON company.id = api_service_credential.company_id
                            WHERE client_assoc_api_service_credential.client_id = $clientId;";

        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByColumn($column, $condition) {
        $query = "SELECT 	id,
                            login_name
                            FROM api_service_credential
                            WHERE $column = '$condition'";

        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function create($bodyContent) {
        $query = "INSERT INTO api_service_credential (login_name, password, status, alteration_date, company_id) VALUES (:login_name, :password, :status,:alteration_date, :company_id);";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(":login_name", $bodyContent['lg_name']);
            $this->bindParam(":password", $bodyContent['pwd']);
            $this->bindParam(":status", 'A');
            // $this->bindParam(":token", $bodyContent['token']);
            $this->bindParam(":alteration_date", date('y/m/d H:i:s'));
            $this->bindParam(":company_id", $bodyContent['company_id']);
            $this->insertPreparedQuery('mysql');

            return $this->lastInsertId('mysql');
            // return $this->select('SELECT LAST_INSERT_ID() AS last_api_id;', 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function deleteApi($apiId) {
    //     $query = "DELETE FROM api_service_credential WHERE id = :where_id;";

    //     try {
    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(":where_id", $apiId);
    //         $this->insertPreparedQuery('mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function update($bodyContent, $apiId) {
        $query = "UPDATE api_service_credential SET login_name = :login_name, status = :status, password = :password, alteration_date = :alteration_date, company_id = :company_id
                    WHERE id = :where_id;";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(":login_name", $bodyContent['lg_name']);
            $this->bindParam(":password", $bodyContent['pwd']);
            // $this->bindParam(":token", $bodyContent['token']);
            $this->bindParam(":status", $bodyContent['status']);
            $this->bindParam(":alteration_date", date('y/m/d H:i:s'));
            $this->bindParam(":company_id", $bodyContent['company_id']);
            $this->bindParam(":where_id", $apiId);
            $this->insertPreparedQuery('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}