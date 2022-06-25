<?php

namespace DataBase;

class BookingContactNotify extends DataBase {
    // public function fetch($apiServiceId) {
    // }

    // public function fetchAll() {
    // }

    public function create($datas) {
        $query = "INSERT INTO booking_contact_notify (email, phone_number, ddd, country_code, first_name, middle_name, last_name, booking_log_id) 
            VALUES (:email, :phone_number, :ddd, :country_code, :first_name, :middle_name, :last_name, :booking_log_id);";

        $middleName = empty($datas['middle_name']) ? 'NULL' : $datas['middle_name'];
        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':email',  $datas['email']);
            $this->bindParam(':phone_number', $datas['phone_number']);
            $this->bindParam(':ddd', $datas['ddd']);
            $this->bindParam(':country_code', $datas['country_code']);
            $this->bindParam(':first_name', $datas['first_name']);
            $this->bindParam(':middle_name', $middleName);
            $this->bindParam(':last_name', $datas['last_name']);
            $this->bindParam(':booking_log_id', $datas['booking_log_id']);
            $this->insertPreparedQuery('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}