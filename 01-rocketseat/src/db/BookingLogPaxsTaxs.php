<?php

namespace DataBase;

class BookingLogPaxsTaxs extends DataBase {

    public function create(Array $datas) {
        $query = "INSERT INTO booking_log_pax_taxs (amount, type, booking_log_pax_id)
                    VALUES (:amount, :type, :booking_log_pax_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':amount', $datas['amount']);
        $this->bindParam(':type', $datas['fare_type']);
        $this->bindParam(':booking_log_pax_id', $datas['booking_log_pax_id']);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }
}