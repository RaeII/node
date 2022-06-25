<?php

namespace DataBase;

class ClientAssocApiService extends DataBase {
    public function create($apiId, $clientId) {
        $query = "INSERT INTO client_assoc_api_service_credential (client_id, api_service_credential_id, alteration_date) VALUES (:client_id, :api_service_credential_id, :alteration_date);";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':client_id', $clientId);
        $this->bindParam(':api_service_credential_id', $apiId);
        $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
        $this->insertPreparedQuery('mysql');
    }

    public function remove($assocId) {
        $query = "DELETE FROM client_assoc_api_service_credential WHERE id = :where_id";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':where_id', $assocId);
        $this->delete($query, 'mysql');
    }

    public function deleteAssocByIds($apiId, $clientId) {
        $query = "DELETE FROM client_assoc_api_service_credential WHERE api_service_credential_id = :apiId AND client_id = :clientId";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':apiId', $apiId);
        $this->bindParam(':clientId', $clientId);
        $this->delete($query, 'mysql');
    }

    public function deleteAssocByApi($apiId) {
        $query = "DELETE FROM client_assoc_api_service_credential WHERE api_service_credential_id = :where_id";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':where_id', $apiId);
        $this->delete($query, 'mysql');
    }

    public function selectApiAssocByAssoc($apiId, $clientId) {
        $query = "SELECT id FROM client_assoc_api_service_credential WHERE api_service_credential_id = $apiId AND client_id = $clientId";

        return $this->select($query, 'mysql');
    }

    public function fetchByClient(Int $clientId, String $compCode) {
        $query = "SELECT    api_service_credential.id apiServiceId,
                            api_service_credential.login_name AS loginName, 
                            api_service_credential.credential_label,
                            api_service_credential.password,
                            api_service_credential.office_code,
                            api_service_credential.accounting_code, 
                            company.id AS companyId,
                            company.wsdl_url AS wsdlUrl, 
                            company.endpoint, 
                            company.token
                        FROM client_assoc_api_service_credential AS assoc 
                            INNER JOIN api_service_credential ON api_service_credential.id = assoc.api_service_credential_id 
                            INNER JOIN company ON company.id = api_service_credential.company_id 
                        WHERE company.code = '$compCode' AND assoc.client_id = $clientId AND api_service_credential.status = 'A';";

        return $this->select($query, 'mysql');
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