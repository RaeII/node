<?php

namespace DataBase;

class BookingLogPaxs extends DataBase {

    public function create(Array $datas) {
        $query = "INSERT INTO booking_log_paxs (first_name, last_name, genre, type, booking_log_id)
                    VALUES (:first_name, :last_name, :genre, :type, :booking_log_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':first_name', $datas['first_name']);
        $this->bindParam(':last_name', $datas['last_name']);
        $this->bindParam(':genre', $datas['genre']);
        $this->bindParam(':type', $datas['type']);
        $this->bindParam(':booking_log_id', $datas['booking_log_id']);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }
}