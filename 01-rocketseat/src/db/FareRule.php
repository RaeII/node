<?php

namespace DataBase;

class FareRule extends DataBase {

    public function fetchAll() {
        $query = "SELECT 
            fare_rules.action, 
            action_is_valid, 
            fare_category, 
            fare_class, 
            value_dec, 
            value_perc,
            fixed_value,
            code AS company_code 
            FROM fare_rules INNER JOIN company ON company.id = company_id;";

        try {
            $result = $this->select($query, 'mysql');

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function fetch($id) {

    // }

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