<?php

namespace Service;

use \Service\Service;
use \DataBase\BookingLogSegments;

class BookingLogSegmentsService extends Service {

    public function __construct() {
        $this->dataBase = new BookingLogSegments();
    }

    /*
        @param $segments Journeys segments from getBooking.
    */
    public function create(Array $segments, Int $journeyId) {
        foreach ($segments as $segment) {
            $this->dataBase->create($segment, $journeyId);
        }
    }

    // public function fetchByLoc(String $loc, Int $userId = null) {
    //     return $this->dataBase->fetchByLoc($loc, $userId);
    // }
}