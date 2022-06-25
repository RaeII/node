<?php

namespace DataBase;

class BookingLogJourneyFares extends DataBase {

    public function create(Array $datas, String $productClass, String $serviceClass, Int $journeyId) {
        $query = "INSERT INTO booking_log_journey_fares (tariff_amount, product_class, service_class, fare_type, 
                                promo_code, promo_code_value, booking_log_journeys_id)
                    VALUES (:tariff_amount, :product_class, :service_class, :fare_type, 
                                :promo_code, :promo_code_value, :booking_log_journeys_id)";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':tariff_amount', $datas['amount']);
        $this->bindParam(':product_class', $productClass);
        $this->bindParam(':service_class', $serviceClass);
        $this->bindParam(':fare_type', $datas['fare_type']);
        $this->bindParam(':promo_code', isset($datas['promotional_code']) ? $datas['promotional_code'] : 'NULL');
        $this->bindParam(':promo_code_value', isset($datas['promotional']) ? $datas['promotional'] : '0');
        $this->bindParam(':booking_log_journeys_id', $journeyId);

        $this->insertPreparedQuery('mysql');

        return $this->lastInsertId('mysql');
    }
}