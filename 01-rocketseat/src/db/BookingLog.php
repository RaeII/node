<?php

namespace DataBase;

class BookingLog extends DataBase {

    public function create(Array $data, Int $errorSumming) {
        $query = "INSERT INTO booking_log (locator, full_trip_route, pax_qtt, total_paxs_taxs, total_journeys_taxs, 
                            total_discount, total_tariff, alteration_date, reg_date, 
                            status, api_service_credential_id, user_account_id, company_code, error_summing)
                    VALUES (:locator, :full_trip_route, :pax_qtt, :total_paxs_taxs, :total_journeys_taxs, 
                                :total_discount, :total_tariff, :alteration_date, :reg_date, 
                                :status, :api_service_credential_id, :user_account_id, :company_code, :error_summing)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':locator', $data['locator']);
        $this->bindParam(':full_trip_route', $data['full_trip_route']);
        $this->bindParam(':pax_qtt', $data['pax_qtt']);
        $this->bindParam(':total_paxs_taxs', $data['total_paxs_taxs']);
        $this->bindParam(':total_journeys_taxs', $data['total_journeys_taxs']);
        $this->bindParam(':total_discount', $data['promotional_discount']);
        $this->bindParam(':total_tariff', $data['total_tariff']);
        $this->bindParam(':alteration_date', $data['alteration_date']);
        $this->bindParam(':reg_date', $data['reg_date']);
        $this->bindParam(':status', $data['status']);
        $this->bindParam(':api_service_credential_id', $data['credential_id']);
        $this->bindParam(':user_account_id',$data['user_account_id']);
        $this->bindParam(':company_code', $data['comp_code']);
        $this->bindParam(':error_summing', $errorSumming);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }

    public function fetchByLoc($loc, $userId) {
        $query = "SELECT locator, status, api_service_credential_id, user_account_id, company_code, error_summing FROM booking_log WHERE locator = '$loc'";

        // Just ignored by Super users (user_account.account_level = '0'). 
        if($userId != null) {
            $query .= " AND user_account_id = $userId";
        }

        $response = $this->select($query, 'mysql');
        if(count($response) > 0) {
            return $response[0];
        }else {
            return [];
        }
    }

    public function updateStatus(String $loc, String $status) {
        $query = "UPDATE booking_log SET status = '$status', alteration_date = now() WHERE locator = '$loc'";

        // Just ignored by Super users (user_account.account_level = '0'). 

        try {
            $this->setSqlManager($query, 'mysql');
            $this->insertPreparedQuery('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
