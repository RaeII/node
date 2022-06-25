<?php

namespace Service;

use \Util\SecureData;
use \Util\Validator;
use \Service\Service;
use \DataBase\BookingRegLocs;

class BookingRegLocsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingRegLocs();
    }

    public function create(Array $data, Int $bookingRegId) {
        $NEEDED_FIELDS = ['locator'];

        Validator::validateJSONKeys($data, $NEEDED_FIELDS);
        $data['locator'] = SecureData::secure($data['locator'], 'Localizador');
        $data['booking_reg_id'] = SecureData::secure($bookingRegId, 'Booking Id');
        $this->dataBase->create($data);
    }
}