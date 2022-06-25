<?php

namespace DataBase;

class BookingRegMarkups extends DataBase {

    public function create(Array $markup, Int $bookingRegId) {
        $query = "INSERT INTO booking_reg_markups (role, value_type, value, description, booking_reg_id)
                    VALUES (:role, :value_type, :value, :description, :booking_reg_id);";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':role', $markup['role']);
        $this->bindParam(':value_type', $markup['value_type']);
        $this->bindParam(':value', $markup['value']);
        $this->bindParam(':description', $markup['description']);
        $this->bindParam(':booking_reg_id', $bookingRegId);
        $this->insertPreparedQuery('mysql');
    }

    public function fetchByLocator(String $locator) {
        $query = "SELECT br_m.id, role, value_type, value, description 
                    FROM booking_reg_markups AS br_m
                    INNER JOIN booking_reg_locs AS br_l ON br_l.booking_reg_id = br_m.booking_reg_id
                    WHERE locator = :locator";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':locator', $locator);
        $res = $this->_select('mysql');

        return $res;
    }

    public function fetchSumByLocator(String $locator) {
        $query = "SELECT    (SELECT COALESCE(SUM(value), 0) FROM booking_reg_markups WHERE booking_reg_id = br_m.booking_reg_id AND value_type = 'VAL') AS total_value,
                            (SELECT COALESCE(SUM(value), 0) FROM booking_reg_markups WHERE booking_reg_id = br_m.booking_reg_id AND value_type = 'PER') AS total_perc
                    FROM booking_reg_markups AS br_m
                    INNER JOIN booking_reg_locs AS br_l ON br_l.booking_reg_id = br_m.booking_reg_id 
                    WHERE locator = :locator LIMIT 1;";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':locator', $locator);
        $res = $this->_select('mysql');

        return $res;
    }
}