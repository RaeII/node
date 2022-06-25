<?php

namespace DataBase;

class BookingLogJourneyFareTaxs extends DataBase {

    public function create(Array $datas, Int $journeyFaresId) {
        $query = "INSERT INTO booking_log_journey_fare_taxs (type, value, booking_log_journey_taxs_id)
                    VALUES (:type, :value, :booking_log_journey_taxs_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':type', $datas['type']);
        $this->bindParam(':value', $datas['total']);
        $this->bindParam(':booking_log_journey_taxs_id', $journeyFaresId);

        $this->insertPreparedQuery('mysql');
    }
}