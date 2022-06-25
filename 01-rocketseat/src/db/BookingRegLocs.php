<?php

namespace DataBase;

class BookingRegLocs extends DataBase {

    public function create(Array $booking) {
        $query = "INSERT INTO booking_reg_locs (locator, description, booking_reg_id)
                    VALUES (:locator, :description, :booking_reg_id);";

        try {
            $this->setSqlManager($query, 'mysql');
            $this->bindParam(':locator', $booking['locator']);
            $this->bindParam(':description', $booking['description']);
            $this->bindParam(':booking_reg_id', $booking['booking_reg_id']);
            $this->insertPreparedQuery('mysql');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // public function fetchByLocator(String $locator) {
    //     $query = "SELECT 
    //                     booking_reg.apply_markup AS applyMarkup,
    //                     booking_reg.apply_promo_code AS applyPromoCode,
    //                     booking_reg.apply_promo_code_repass AS applyPromoCodeRepass,
    //                     booking_reg.promo_code_value_repass AS promoCodeRepassValue,
    //                     booking_reg.fare_equal_net AS fareEqualNet 
    //                 FROM booking_reg WHERE locator = :locator";

    //     $this->setSqlManager($query, 'mysql');
    //     $this->bindParam(':locator', $locator);
    //     $res = $this->_select('mysql');

    //     if(count($res) > 0) {
    //         return $res[0];
    //     }else {
    //         return [];
    //     }
    // }
}