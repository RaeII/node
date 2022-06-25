<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogPaxsTaxs;

class BookingLogPaxsTaxsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogPaxsTaxs();
    }

    /*
        @param $pax Pax fees result from getBooking.
    */
    public function create(Array $fees, Int $paxId) {
        $toLog = [];

        foreach ($fees as $fee) {
            $toLog['amount'] = $fee['amount'];
            $toLog['fare_type'] = $fee['fare_type'];
            $toLog['booking_log_pax_id'] = $paxId;

            $this->dataBase->create($toLog);
        }
    }

    // public function fetchByLoc(String $loc, Int $userId = null) {
    //     // return $this->dataBase->fetchByLoc($loc, $userId);
    // }
}