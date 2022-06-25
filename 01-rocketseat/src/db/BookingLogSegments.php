<?php

namespace DataBase;

class BookingLogSegments extends DataBase {

    public function create(Array $datas, Int $journeysId) {
        $query = "INSERT INTO booking_log_segments (`from`, `to`, dep_date, arr_date, booking_log_journeys_id)
                    VALUES (:from, :to, :dep_date, :arr_date, :booking_log_journeys_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':from', $datas['from']);
        $this->bindParam(':to', $datas['to']);
        $this->bindParam(':dep_date', $datas['dep_date']);
        $this->bindParam(':arr_date', $datas['arr_date']);
        $this->bindParam(':booking_log_journeys_id', $journeysId);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }
}