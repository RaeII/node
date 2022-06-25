<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogJourneys;

class BookingLogJourneysService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogJourneys();
    }

    /*
        @param $segments Segments Result from getBooking.
    */
    public function create(Array $segments, Int $bookingLogId) {
        $toLog['from'] = $segments[0]['from'];
        $toLog['to'] = $segments[count($segments) - 1]['to'];
        $toLog['dep_date'] = $segments[0]['dep_date'];
        $toLog['arr_date'] = $segments[count($segments) - 1]['arr_date'];
        $toLog['comp_code'] = $segments[0]['comp_code'];
        $toLog['register_date'] = date('Y/m/d H:i:s');
        $toLog['booking_log_id'] = $bookingLogId;

        return $this->dataBase->create($toLog);
    }

    public function fetchByLoc(String $loc, Int $userId = null) {
        return $this->dataBase->fetchByLoc($loc, $userId);
    }
}