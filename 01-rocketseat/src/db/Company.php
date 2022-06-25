<?php

namespace DataBase;

class Company extends DataBase {
    public function fetchAll() {
        $query = "SELECT 	company.id AS companyId,
                            company.company_name,
                            company.trading_name
                            FROM company;";
        try {
            return $this->select($query, 'mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetch($id) {
        $query = "SELECT 	company.id AS companyId,
                            company.company_name,
                            company.trading_name
                            FROM company
                            WHERE id = $id";
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
}