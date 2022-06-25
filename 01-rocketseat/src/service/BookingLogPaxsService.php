<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogPaxs;

class BookingLogPaxsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogPaxs();
    }

    /*
        @param $pax Pax result from getBooking.
    */
    public function create(Array $pax, Int $bookingLogId) {
        $toLog = [];

        $toLog['first_name'] = $pax['first_name'];
        $toLog['last_name'] = $pax['last_name'];
        $toLog['genre'] = $pax['gender'] == 'Male' ? 'M' : ($pax['gender'] == 'Female' ? 'F' : '');
        $toLog['type'] = $pax['type'];
        $toLog['booking_log_id'] = $bookingLogId;

        return $this->dataBase->create($toLog);
    }

    // public function fetchByLoc(String $loc, Int $userId = null) {
    //     // return $this->dataBase->fetchByLoc($loc, $userId);
    // }
}