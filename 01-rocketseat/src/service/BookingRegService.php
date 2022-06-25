<?php

namespace Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\Service;
use \DataBase\BookingReg;

class BookingRegService extends Service {

    public function __construct() {
        $this->dataBase = new BookingReg();
    }

    public function create(Array $booking) {
        $NEEDED_KEYS = ['fare_equal_net', 'apply_promo_code', 'apply_promo_code_repass', 
                        'promo_code_value_repass', 'apply_markup'];
        // var_dump($booking);die();
        Validator::validateJSONKeys($booking, $NEEDED_KEYS);

        $booking['fare_equal_net'] = SecureData::secure($booking['fare_equal_net']);
        $booking['apply_promo_code'] = SecureData::secure($booking['apply_promo_code']);
        $booking['apply_promo_code_repass'] = SecureData::secure($booking['apply_promo_code_repass']);
        $booking['promo_code_value_repass'] = SecureData::secure($booking['promo_code_value_repass']);
        $booking['apply_markup'] = SecureData::secure($booking['apply_markup']);

        return $this->dataBase->create($booking);
    }

    public function fetchByLocator(String $locator) {
        return $this->dataBase->fetchByLocator($locator);
    }
}