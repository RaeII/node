<?php
namespace DataBase;

class ClientAssocMarkupAssocCompany extends DataBase {

    public function fetchByAssociations($markupId, $companyId, $clientId) {
        try {
            $query = "SELECT id FROM client_assoc_markup_assoc_company 
                WHERE markup_id = $markupId AND company_id = $companyId AND client_id = $clientId";

            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByClient($clientId) {
        try {
            $query = "SELECT
                        markup.id AS markupId,
                        markup.role,
                        markup.value_type,
                        markup.value,
                        markup.description,
                        company.company_name as companyName,
                        company.trading_name as tradingName
                        FROM client_assoc_markup_assoc_company 
                        INNER JOIN markup ON markup.id = client_assoc_markup_assoc_company.markup_id
                        INNER JOIN company ON company.id = client_assoc_markup_assoc_company.company_id
                        WHERE client_assoc_markup_assoc_company.client_id = $clientId AND markup.status = 'A'";

            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchSumByClient($clientId) {
        $query = "SELECT    COALESCE(SUM((SELECT COALESCE(SUM(value), 0) FROM markup WHERE id = assoc.markup_id AND value_type = 'VAL')), 0) AS total_value,
                            COALESCE(SUM((SELECT COALESCE(SUM(value), 0) FROM markup WHERE id = assoc.markup_id AND value_type = 'PER')), 0) AS total_perc
                    FROM client_assoc_markup_assoc_company AS assoc
                    INNER JOIN markup ON markup.id = assoc.markup_id
                    WHERE assoc.client_id = $clientId AND markup.status = 'A' LIMIT 1;";

        return $this->select($query, 'mysql');
    }

    public function fetchByClientAndRole(Int $clientId, String $role) {
        $query = "SELECT    COALESCE(SUM((SELECT COALESCE(SUM(value), 0) FROM markup WHERE id = assoc.markup_id AND value_type = 'VAL')), 0) AS total_value,
                            COALESCE(SUM((SELECT COALESCE(SUM(value), 0) FROM markup WHERE id = assoc.markup_id AND value_type = 'PER')), 0) AS total_perc
                    FROM client_assoc_markup_assoc_company AS assoc
                    INNER JOIN markup ON markup.id = assoc.markup_id
                    WHERE assoc.client_id = $clientId AND markup.status = 'A' AND markup.role = '$role' LIMIT 1;";

        return $this->select($query, 'mysql');
    }

    // public function selectAssocByUserAccount($clientId) {
    //     try {
    //         $query = "SELECT
    //                     markup.id AS markupId,
    //                     markup.role,
    //                     markup.value,
    //                     markup.description,
    //                     company.company_name as companyName,
    //                     company.trading_name as tradingName
    //                     FROM client_assoc_markup_assoc_company 
    //                     INNER JOIN markup ON markup.id = client_assoc_markup_assoc_company.markup_id
    //                     INNER JOIN company ON company.id = client_assoc_markup_assoc_company.company_id
    //                     INNER JOIN user_account ON company.id = client_assoc_markup_assoc_company.company_id
    //                     WHERE client_assoc_markup_assoc_company.client_id = $clientId";

    //         return $this->select($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    public function fetchByMarkup($markupId) {
        try {
            $query = "SELECT * FROM client_assoc_markup_assoc_company 
                        WHERE client_assoc_markup_assoc_company.markup_id = $markupId";

            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchMarkupByClientAndCode($clientId, $aerialCompCode) {
        try {
            $query = "SELECT    markup.id, 
                                markup.value_type, 
                                markup.value, 
                                markup.role,
                                markup.description
                            FROM client_assoc_markup_assoc_company as assoc 
                        INNER JOIN company ON company.id = assoc.company_id
                        INNER JOIN markup ON markup.id = assoc.markup_id
                        WHERE assoc.client_id = $clientId AND company.code = '$aerialCompCode'";

            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function create($markupId, $companyId, $clientId) {
        try {
            $query = "INSERT INTO client_assoc_markup_assoc_company (client_id, markup_id, company_id) VALUES (:client_id, :markup_id, :company_id);";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':client_id', $clientId);
            $this->bindParam(':markup_id', $markupId);
            $this->bindParam(':company_id', $companyId);
            $this->insertPreparedQuery('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove($assocId) {
        try {
            $query = "DELETE FROM client_assoc_markup_assoc_company WHERE id = :where_id";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':where_id', $assocId);
            $this->delete($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function removeByClient($clientId) {
        try {
            $query = "DELETE FROM client_assoc_markup_assoc_company WHERE client_id = :where_id";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':where_id', $clientId);
            $this->delete($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function removeByCompany($companyId) {
    //     try {
    //         $query = "DELETE FROM client_assoc_markup_assoc_company WHERE company_id = :where_id";

    //         $this->setSqlManager($query, 'mysql');
    //         $this->bindParam(':where_id', $companyId);
    //         $this->delete($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }


    public function removeByMarkup($markupId) {
        try {
            $query = "DELETE FROM client_assoc_markup_assoc_company WHERE markup_id = :where_id";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':where_id', $markupId);
            $this->delete($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function fetchByMarkup($markupId) {
    //     try {
    //         $query = "SELECT id FROM client_assoc_markup_assoc_company 
    //             WHERE markup_id = $markupId AND company_id = $companyId AND client_id = $clientId";

    //         return $this->select($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    // public function selectMarkupAssocByAssoc($markupId, $clientId) {
    //     try {
    //         $query = "SELECT id FROM client_assoc_markup_assoc_company WHERE markup_id = $markupId AND client_id = $clientId";

    //         return $this->select($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }

    // }
}