<?php

namespace DataBase;

class BookingReg extends DataBase {

    public function create(Array $booking) {
        $query = "INSERT INTO booking_reg (fare_equal_net, apply_promo_code, apply_promo_code_repass, promo_code_value_repass, apply_markup)
                    VALUES (:fare_equal_net, :apply_promo_code, :apply_promo_code_repass, :promo_code_value_repass, :apply_markup);";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':fare_equal_net', $booking['fare_equal_net']);
            $this->bindParam(':apply_promo_code', $booking['apply_promo_code']);
            $this->bindParam(':apply_promo_code_repass', $booking['apply_promo_code_repass']);
            $this->bindParam(':promo_code_value_repass', $booking['promo_code_value_repass']);
            $this->bindParam(':apply_markup', $booking['apply_markup']);

            $this->insertPreparedQuery('mysql');

            return $this->lastInsertId('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function fetchByLocator(String $locator) {
        $query = "SELECT
                        booking_reg.id AS id, 
                        booking_reg.apply_markup AS applyMarkup,
                        booking_reg.apply_promo_code AS applyPromoCode,
                        booking_reg.apply_promo_code_repass AS applyPromoCodeRepass,
                        booking_reg.promo_code_value_repass AS promoCodeRepassValue,
                        booking_reg.fare_equal_net AS fareEqualNet
                    FROM booking_reg 
                        INNER JOIN booking_reg_locs AS br_l ON br_l.booking_reg_id = booking_reg.id
                    WHERE br_l.locator = :locator";

        $this->setSqlManager($query, 'mysql');
        $this->bindParam(':locator', $locator);
        $res = $this->_select('mysql');

        if(count($res) > 0) {
            return $res[0];
        }else {
            return [];
        }
    }
}