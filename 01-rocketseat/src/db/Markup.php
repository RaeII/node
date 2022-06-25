<?php

namespace DataBase;

class Markup extends DataBase {

    public function fetchAll() {
        $query = "SELECT 	markup.id AS markupId,
                            markup.description,
                            markup.role,
                            markup.value_type,
                            markup.value,
                            -- JSON_ARRAYAGG(client.company_name) AS clients_company_name,
                            -- JSON_ARRAYAGG(company.company_name) AS companys_company_name
                            JSON_ARRAYAGG(client.id) AS clients_id,
                            JSON_ARRAYAGG(company.id) AS companys_id
                            FROM client_assoc_markup_assoc_company AS assoc
                                INNER JOIN client ON client.id = assoc.client_id
                                INNER JOIN company ON company.id =  assoc.company_id
                                INNER JOIN markup ON markup.id = assoc.markup_id
                            GROUP BY markup.id;";
        try {
            $result = $this->select($query, 'mysql');
            foreach ($result as &$markup) {
                $markup['clients_id'] = json_decode($markup['clients_id'], true);
                $markup['companys_id'] = json_decode($markup['companys_id'], true);
            }
            unset($markup);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetch($id) {
        $query = "SELECT 	markup.id AS markupId,
                            markup.role,
                            markup.value_type,
                            markup.value
                            markup.description,
                            FROM markup
                            WHERE id = $id AND status = 'A'";
        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function create($markup) {
        try {
            $query = "INSERT INTO markup (description, role, value_type, value, alteration_date) VALUES (:description, :role, :value_type, :value, :alteration_date);";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':description', $markup['description']);
            $this->bindParam(':role', $markup['role']);
            $this->bindParam(':value_type', $markup['value_type']);
            $this->bindParam(':value', $markup['value']);
            $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
            $this->insertPreparedQuery('mysql');

            return $this->lastInsertId('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function remove($markupId) {
        try {
            $query = "DELETE FROM markup WHERE id = $markupId";
            
            $this->delete($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($markup, $markupId) {
        try {
            $query = "UPDATE markup SET description = :description, role = :role, value_type = :value_type, value = :value, alteration_date = :alteration_date WHERE id = :where_id";

            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':description', $markup['description']);
            $this->bindParam(':role', $markup['role']);
            $this->bindParam(':value_type', $markup['value_type']);
            $this->bindParam(':value', $markup['value']);
            $this->bindParam(':alteration_date', date('y/m/d H:i:s'));
            $this->bindParam(':where_id', $markupId);
            $this->_update($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function selectMarkupByClient($clientId) {
    //     try {
    //         $query = "SELECT 	markup.id AS markupId,
    //                             markup.role,
    //                             markup.value
    //                             FROM client_assoc_markup_assoc_company AS assoc
    //                             INNER JOIN markup_assoc_company AS assoc_company ON assoc_company.id = assoc.markup_assoc_company_id
    //                             INNER JOIN markup ON markup.id = assoc_company.markup_id
    //                             WHERE assoc.client_id = $clientId;";
    //         return $this->select($query, 'mysql');
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }
}