<?php

namespace DataBase;

class BookingLogJourneys extends DataBase {

    public function create(Array $datas) {
        $query = "INSERT INTO booking_log_journeys (`from`, `to`, dep_date, arr_date, comp_code, register_date, booking_log_id)
                    VALUES (:from, :to, :dep_date, :arr_date, :comp_code, :register_date, :booking_log_id);";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':from', $datas['from']);
        $this->bindParam(':to', $datas['to']);
        $this->bindParam(':dep_date', $datas['dep_date']);
        $this->bindParam(':arr_date', $datas['arr_date']);
        $this->bindParam(':comp_code', $datas['comp_code']);
        $this->bindParam(':register_date', $datas['register_date']);
        $this->bindParam(':booking_log_id', $datas['booking_log_id']);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }
}