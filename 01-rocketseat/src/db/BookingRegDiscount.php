<?php

namespace DataBase;

class BookingRegDiscount extends DataBase {

    public function create(Array $booking, Int $bookingRegId) {
        $query = "INSERT INTO booking_reg_discounts (code, value, pax_id, booking_reg_id)
                    VALUES (:code, :value, :pax_id, :booking_reg_id);";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':code', $booking['code']);
            $this->bindParam(':value', $booking['value']);
            $this->bindParam(':pax_id', $booking['pax_id']);
            $this->bindParam(':booking_reg_id', $bookingRegId);

            $this->insertPreparedQuery('mysql');

            return $this->lastInsertId('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchSumByLocator(String $locator) {
        $query = "SELECT SUM(br_d.value) AS total_value 
                    FROM booking_reg_discounts AS  br_d
                    INNER JOIN booking_reg_locs AS br_l ON br_l.booking_reg_id = br_d.booking_reg_id WHERE br_l.locator = :locator";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':locator', $locator);
            $res = $this->_select('mysql');

            return $res;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}